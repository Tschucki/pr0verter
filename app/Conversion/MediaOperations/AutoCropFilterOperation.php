<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use FFMpeg\Filters\Video\VideoFilters;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class AutoCropFilterOperation implements MediaFilterOperation
{
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
        $timeout = config('laravel-ffmpeg.timeout');

        $escapedPath = escapeshellarg($path);

        $command = "{$ffmpeg} -i {$escapedPath} -vf cropdetect -f null - 2>&1 | grep -o 'crop=[0-9:]*' | tail -1";

        $process = Process::timeout(3 * 60)->run($command);

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
