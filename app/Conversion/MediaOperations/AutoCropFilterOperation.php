<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use FFMpeg\Filters\Video\VideoFilters;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class AutoCropFilterOperation implements MediaFilterOperation
{
    private const DETECTION_TIMEOUT_SECONDS = 180;

    public Conversion $conversion;

    private string $crop;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
        $this->prepareData();
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        $cropFilter = $this->crop;

        if (! empty($cropFilter) && str_contains($cropFilter, 'crop=')) {
            $media->addFilter(function (VideoFilters $filters) use ($cropFilter) {
                $filters->custom($cropFilter);
            });
        }

        return $media;
    }

    private function prepareData(): void
    {
        $path = Storage::disk($this->conversion->file->disk)->path($this->conversion->file->filename);

        $ffmpeg = config('laravel-ffmpeg.ffmpeg.binaries');

        $escapedPath = escapeshellarg($path);

        // Decoding only keyframes keeps cropdetect fast on long videos, where a
        // full decode used to run into the timeout. Audio is not decoded at all.
        $command = "{$ffmpeg} -skip_frame nokey -i {$escapedPath} -vf cropdetect -an -f null - 2>&1 | grep -o 'crop=[0-9:]*' | tail -1";

        try {
            $process = Process::timeout(self::DETECTION_TIMEOUT_SECONDS)->run($command);
        } catch (ProcessTimedOutException) {
            // Cropping is optional: convert uncropped instead of failing the job.
            Log::warning('AutoCrop detection timed out, converting without crop', [
                'conversion_id' => $this->conversion->id,
                'timeout' => self::DETECTION_TIMEOUT_SECONDS,
            ]);

            $this->crop = '';

            return;
        }

        $output = trim($process->output());

        if (! empty($output) && preg_match('/^crop=(\d+):(\d+):(\d+):(\d+)$/', $output, $matches)) {
            $cropWidth = (int) $matches[1];
            $cropHeight = (int) $matches[2];
            $cropX = (int) $matches[3];
            $cropY = (int) $matches[4];

            $originalWidth = $this->conversion->metadata['width'] ?? 0;
            $originalHeight = $this->conversion->metadata['height'] ?? 0;

            $isValid = $cropWidth > 0 &&
                       $cropHeight > 0 &&
                       $cropX >= 0 &&
                       $cropY >= 0 &&
                       $originalWidth > 0 &&
                       $originalHeight > 0 &&
                       ($cropX + $cropWidth) <= $originalWidth &&
                       ($cropY + $cropHeight) <= $originalHeight;

            if ($isValid) {
                $this->crop = $output;
            } else {
                Log::warning('AutoCrop parameters are invalid or out of bounds', [
                    'conversion_id' => $this->conversion->id,
                    'crop_output' => $output,
                    'original_width' => $originalWidth,
                    'original_height' => $originalHeight,
                    'crop_width' => $cropWidth,
                    'crop_height' => $cropHeight,
                ]);

                $this->crop = '';
            }
        } else {
            Log::warning('AutoCrop detection failed or returned invalid output', [
                'conversion_id' => $this->conversion->id,
                'output' => $output,
                'exit_code' => $process->exitCode(),
            ]);

            $this->crop = '';
        }
    }
}
