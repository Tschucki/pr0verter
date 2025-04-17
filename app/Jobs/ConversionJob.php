<?php

namespace App\Jobs;

use App\Enums\ConversionStatus;
use App\Events\ConversionProgressEvent;
use App\Models\Conversion;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Format\Video\X264;
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

        $conversionNeeded = $this->checkIfConversionIsActuallyNeeded($conversion);

        if ($conversionNeeded === false) {
            $conversion->update([
                'status' => 'finished',
                'downloadable' => true,
            ]);

            return;
        }

        $conversion = $conversion->loadMissing('file');

        // the file needs to be named differently as the input can not be the same as Input
        if ($conversion->audio_only) {
            $format = new Mp3;
            $newFileName = pathinfo($conversion->file->filename, PATHINFO_FILENAME) . '.mp3';
        } else {
            $format = new X264;
            $newFileName = $conversion->file->convertedFileName();
        }

        $formatOperations = $conversion->getFormatOperations();
        $mediaOperations = $conversion->getMediaOperations();

        $media = FFMpeg::fromDisk($conversion->file->disk)
            ->open($conversion->file->filename);

        foreach ($mediaOperations as $operation) {
            $media = $operation->applyToMedia($media);
        }

        foreach ($formatOperations as $operation) {
            $format = $operation->applyToFormat($format);
        }

        $conversion->update([
            'status' => 'processing',
        ]);

        try {
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
                        $conversion->id, $percentage, $remaining, $rate);
                })
                ->toDisk($conversion->file->disk)
                ->save($newFileName)
                ->cleanupTemporaryFiles();
        } catch (Throwable $e) {
            Log::error('Error while processing conversion', [
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

            return;
        }

        $conversion->file->update([
            'filename' => $newFileName,
            'extension' => pathinfo($newFileName, PATHINFO_EXTENSION),
            'size' => Storage::disk($conversion->file->disk)->size($newFileName),
            'mime_type' => Storage::disk($conversion->file->disk)->mimeType($newFileName),
        ]);

        $conversion->update([
            'status' => 'finished',
            'downloadable' => true,
        ]);
    }

    private function checkIfConversionIsActuallyNeeded(Conversion $conversion): bool
    {
        $file = $conversion->file;
        $storage = Storage::disk($file->disk);
        $extension = pathinfo($storage->path($file->filename), PATHINFO_EXTENSION);

        if ($conversion->audio_only) {
            return $extension !== 'mp3';
        }

        $hasCompatibleFormat = in_array($extension, ['mp4', 'webm'], true);
        $isWithinSizeLimit = $storage->size($file->filename) <= $conversion->max_size * 1024 * 1024;

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

        return ! ($hasCompatibleFormat && $isWithinSizeLimit);
    }

    private function shouldContinue(): bool
    {
        $conversion = Conversion::find($this->conversionId);

        return $conversion && $conversion->status !== ConversionStatus::CANCELED;
    }
}
