<?php

namespace App\Jobs;

use App\Events\ConversionProgressEvent;
use App\Models\Conversion;
use FFMpeg\Format\Video\X264;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
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

        $conversionNeeded = $this->checkIfConversionIsActuallyNeeded($conversion);

        if ($conversionNeeded === false) {
            return;
        }

        $conversion = $conversion->loadMissing('file');

        // the file needs to be named differently as the input can not be the same as Input
        $newFileName = $conversion->file->convertedFileName();
        $format = new X264;

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
                    if ($percentage % 5 !== 0) {
                        return;
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

            $conversion->update([
                'status' => 'failed',
            ]);

            return;
        }

        $conversion->file->update([
            'filename' => $newFileName,
            'extension' => 'mp4',
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
        $hasAdditionalOperations = false;
        $hasCorrectExtension = false;
        $hasCorrectSize = false;

        $file = Storage::disk($conversion->file->disk)->path($conversion->file->filename);
        $size = Storage::disk($conversion->file->disk)->size($conversion->file->filename);

        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if ($extension === 'mp4' || $extension === 'webm') {
            $conversion->update([
                'status' => 'finished',
                'downloadable' => true,
            ]);
            Log::info('Datei ist bereits mp4 oder webm');
            $hasCorrectExtension = true;
        }

        if ($size <= $conversion->max_size * 1024 * 1024) {
            $conversion->update([
                'status' => 'finished',
                'downloadable' => true,
            ]);

            $hasCorrectSize = true;
        }

        if (
            $conversion->audio_quality !== 1 &&
            $conversion->audio === false &&
            $conversion->watermark === true &&
            $conversion->auto_crop === true &&
            $conversion->trim_start !== null &&
            $conversion->trim_end !== null
        ) {
            $conversion->update([
                'status' => 'finished',
                'downloadable' => true,
            ]);

            $hasAdditionalOperations = true;
        }

        return ! $hasCorrectExtension || ! $hasCorrectSize || $hasAdditionalOperations;
    }
}
