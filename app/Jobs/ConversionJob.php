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
}
