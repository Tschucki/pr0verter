<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
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
        $media->addFilter(['-vf', $this->crop]);

        return $media;
    }

    private function prepareData(): void
    {
        $path = Storage::disk($this->conversion->file->disk)->path($this->conversion->file->filename);

        $ffmpeg = config('laravel-ffmpeg.ffmpeg.binaries');
        $timeout = config('laravel-ffmpeg.timeout');

        $process = Process::run("{$ffmpeg} -timeout {$timeout} -flags2 +export_mvs -i {$path} -vf cropdetect -f null - 2>&1 | awk '/crop/ { print \$NF }' | tail -1");

        $this->crop = trim($process->output());
    }
}
