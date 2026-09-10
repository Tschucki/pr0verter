<?php

namespace App\Jobs;

use App\Conversion\Formats\H264Format;
use App\Enums\ConversionStatus;
use App\Events\ConversionProgressEvent;
use App\Models\Conversion;
use App\Services\ThumbnailService;
use App\Services\VideoAnalysisService;
use App\ValueObjects\VideoMetadata;
use FFMpeg\FFProbe;
use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use RuntimeException;
use Throwable;

class ConversionJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use Queueable;

    private string $conversionId;

    public function __construct(string $conversionId)
    {
        $this->conversionId = $conversionId;
    }

    public function uniqueId(): string
    {
        return $this->conversionId;
    }

    public function handle(): void
    {
        $conversion = Conversion::find($this->conversionId);

        if ($conversion === null) {
            return;
        }

        $conversion->update([
            'status' => 'preparing',
        ]);

        if (! $this->shouldContinue()) {
            return;
        }

        $conversion = $conversion->loadMissing('file');

        if ($conversion->raw_download) {
            $this->finishWithoutEncoding($conversion);

            return;
        }

        $filePath = Storage::disk($conversion->file->disk)->path($conversion->file->filename);
        $analysisService = app(VideoAnalysisService::class);
        $metadata = $analysisService->analyze($filePath);

        $qualityTier = $metadata->getQualityTier();

        $conversion->update([
            'metadata' => [
                'width' => $metadata->width,
                'height' => $metadata->height,
                'duration' => $metadata->duration,
                'framerate' => $metadata->framerate,
                'rotation' => $metadata->rotation,
                'audio_sample_rate' => $metadata->audioSampleRate,
                'video_codec' => $metadata->videoCodec?->value,
                'audio_codec' => $metadata->audioCodec?->value,
            ],
            'quality_tier' => $qualityTier->value,
        ]);

        $conversion->refresh();
        if ($conversion->thumbnail_path === null
            && $metadata->videoCodec !== null
            && $conversion->audio_only === false
        ) {
            app(ThumbnailService::class)
                ->captureSourceFrame($conversion, $metadata->duration);
        }

        $conversionNeeded = $this->checkIfConversionIsActuallyNeeded($conversion, $metadata);

        if ($conversionNeeded === false) {
            $conversion->update([
                'status' => 'finished',
                'downloadable' => true,
            ]);

            return;
        }

        if ($conversion->audio_only) {
            $format = new Mp3;
            $newFileName = pathinfo($conversion->file->filename, PATHINFO_FILENAME) . '.mp3';
        } else {
            $format = new H264Format($qualityTier);
            $baseFileName = pathinfo($conversion->file->filename, PATHINFO_FILENAME);

            if (pathinfo($conversion->file->filename, PATHINFO_EXTENSION) === 'mp4') {
                $newFileName = $baseFileName . '_converted.mp4';
            } else {
                $newFileName = $baseFileName . '.mp4';
            }
        }

        $this->performEncoding($conversion, $format, $newFileName);
    }

    /**
     * Raw downloads keep the source file untouched: no media operations, no
     * size limit, no re-encoding. Metadata and thumbnail are best-effort only
     * so the list view still has something to show.
     */
    private function finishWithoutEncoding(Conversion $conversion): void
    {
        try {
            $filePath = Storage::disk($conversion->file->disk)->path($conversion->file->filename);
            $metadata = app(VideoAnalysisService::class)->analyze($filePath);

            $conversion->update([
                'metadata' => [
                    'width' => $metadata->width,
                    'height' => $metadata->height,
                    'duration' => $metadata->duration,
                    'framerate' => $metadata->framerate,
                    'rotation' => $metadata->rotation,
                    'audio_sample_rate' => $metadata->audioSampleRate,
                    'video_codec' => $metadata->videoCodec?->value,
                    'audio_codec' => $metadata->audioCodec?->value,
                ],
                'quality_tier' => $metadata->getQualityTier()->value,
            ]);

            $conversion->refresh();

            if ($conversion->thumbnail_path === null && $metadata->videoCodec !== null) {
                app(ThumbnailService::class)->captureSourceFrame($conversion, $metadata->duration);
            }
        } catch (Throwable $e) {
            Log::warning('Raw download analysis failed', [
                'conversionId' => $conversion->id,
                'exception' => $e->getMessage(),
            ]);
        }

        $conversion->update([
            'status' => ConversionStatus::FINISHED,
            'downloadable' => true,
        ]);
    }

    private function checkIfConversionIsActuallyNeeded(Conversion $conversion, VideoMetadata $metadata): bool
    {
        $file = $conversion->file;
        $storage = Storage::disk($file->disk);
        $extension = pathinfo($storage->path($file->filename), PATHINFO_EXTENSION);

        if ($conversion->audio_only) {
            return $extension !== 'mp3';
        }

        $hasCompatibleCodec = $metadata->videoCodec && $metadata->videoCodec->isSupported();
        $hasCompatibleFormat = in_array($extension, ['mp4', 'webm', 'mov'], true);

        $isWithinSizeLimit = true;
        if ($conversion->max_size) {
            $isWithinSizeLimit = $storage->size($file->filename) <= $conversion->max_size * 1024 * 1024;
        }

        $hasCustomOperations =
            $conversion->audio_quality !== 1 ||
            $conversion->audio === false ||
            $conversion->watermark === true ||
            $conversion->auto_crop === true ||
            $conversion->trim_start !== null ||
            $conversion->trim_end !== null ||
            ! empty($conversion->segments);

        if ($hasCustomOperations) {
            return true;
        }

        return ! ($hasCompatibleCodec && $hasCompatibleFormat && $isWithinSizeLimit);
    }

    private function shouldContinue(): bool
    {
        $conversion = Conversion::find($this->conversionId);

        return $conversion && $conversion->status !== ConversionStatus::CANCELED;
    }

    private function performEncoding(
        Conversion $conversion,
        DefaultAudio $format,
        string $newFileName,
    ): void {
        $mediaOperations = $conversion->getMediaOperations();

        $media = FFMpeg::fromDisk($conversion->file->disk)
            ->open($conversion->file->filename);

        foreach ($mediaOperations as $operation) {
            $media = $operation->applyToMedia($media);
        }

        $conversion->update([
            'status' => 'processing',
        ]);

        $originalFileSize = Storage::disk($conversion->file->disk)->size($conversion->file->filename);

        try {
            Log::info('Starting encoding with Two-Pass', [
                'maxSize' => $conversion->max_size,
                'conversionId' => $conversion->id,
            ]);

            $formatOperations = $conversion->getFormatOperations();
            foreach ($formatOperations as $operation) {
                $format = $operation->applyToFormat($format);
            }

            $media->export()
                ->inFormat($format)
                ->onProgress(function ($percentage, $remaining, $rate) use ($conversion) {
                    if ($percentage % 10 !== 0) {
                        return;
                    }

                    if (! $this->shouldContinue()) {
                        throw new RuntimeException('Konvertierung wurde abgebrochen');
                    }

                    ConversionProgressEvent::dispatch(
                        $conversion->id,
                        $percentage,
                        $remaining,
                        $rate
                    );
                })
                ->toDisk($conversion->file->disk)
                ->save($newFileName)
                ->cleanupTemporaryFiles();

            $outputFileSize = Storage::disk($conversion->file->disk)->size($newFileName);

            Log::info('Encoding completed', [
                'originalSize' => $originalFileSize,
                'outputSize' => $outputFileSize,
                'maxSize' => $conversion->max_size * 1024 * 1024,
            ]);

            $oldFileName = $conversion->file->filename;

            $conversion->file->update([
                'filename' => $newFileName,
                'extension' => pathinfo($newFileName, PATHINFO_EXTENSION),
                'size' => $outputFileSize,
                'mime_type' => Storage::disk($conversion->file->disk)->mimeType($newFileName),
            ]);

            if ($oldFileName !== $newFileName && Storage::disk($conversion->file->disk)->exists($oldFileName)) {
                Storage::disk($conversion->file->disk)->delete($oldFileName);
                Log::info('Deleted old input file', ['filename' => $oldFileName]);
            }

            if ($conversion->audio_only === false) {
                try {
                    $outputAbsolutePath = Storage::disk($conversion->file->disk)->path($newFileName);
                    $outputDuration = (float) FFProbe::create()
                        ->format($outputAbsolutePath)
                        ->get('duration');

                    app(ThumbnailService::class)->captureOutputFrame(
                        $conversion->fresh(),
                        $conversion->file->disk,
                        $newFileName,
                        $outputDuration,
                    );
                } catch (Throwable $e) {
                    Log::warning('Output thumbnail probe/capture failed', [
                        'conversion' => $conversion->id,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }

            $conversion->update([
                'status' => 'finished',
                'downloadable' => true,
            ]);

        } catch (Throwable $e) {
            Log::error('Error while encoding', [
                'conversionId' => $conversion->id,
                'exception' => $e,
            ]);

            if (! $this->shouldContinue()) {
                $conversion->update([
                    'status' => ConversionStatus::CANCELED,
                    'error_message' => null,
                ]);
            } else {
                $conversion->update([
                    'status' => ConversionStatus::FAILED,
                    'error_message' => 'Beim Konvertieren ist ein Fehler aufgetreten.',
                ]);
            }
        }
    }
}
