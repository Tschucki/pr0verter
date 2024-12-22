<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFormatOperation;
use App\Models\Conversion;
use FFMpeg\FFProbe;
use FFMpeg\FFProbe\DataMapping\Format;
use FFMpeg\Format\Video\DefaultVideo;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class MaxSizeOperation implements MediaFormatOperation
{
    public Conversion $conversion;

    private Format $format;

    private $currentAudioBitrate;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
        $this->prepareData();
    }

    public function applyToFormat(DefaultVideo $format): DefaultVideo
    {
        // removing 4MB from the max size to make sure the video fits
        // TODO: feels wrong to hardcode this :/

        $maxSizeInMB = $this->conversion->max_size - 4;
        $duration = $this->actualDuration();
        $audioBitrate = $this->conversion->audio ? $this->currentAudioBitrate * $this->conversion->audio_quality : 0;
        $audioBitrateInKiloBits = $audioBitrate / 1024;
        $maxSizeInBits = $maxSizeInMB * 1024 * 1024 * 8;
        $videoBitrate = floor(($maxSizeInBits / $duration) - floor($audioBitrateInKiloBits));

        if (($videoBitrate * $duration) >= $this->format->get('bit_rate')) {
            return $format;
        }

        if ($videoBitrate < 0) {
            $videoBitrate = 0;
        }

        $kiloBitRate = (int) floor($videoBitrate / 1024);

        $input = Storage::disk($this->conversion->file->disk)->path($this->conversion->file->filename);

        $ffmpeg = config('laravel-ffmpeg.ffmpeg.binaries');
        Process::run("{$ffmpeg} -y -i {$input} -c:v libx264 -b:v {$kiloBitRate}k -pass 1 -f null /dev/null");

        $format->setKiloBitrate($kiloBitRate);
        $format->setPasses(2);

        return $format;
    }

    private function prepareData(): void
    {
        $probe = app(FFProbe::class);
        $this->format = $probe->format(Storage::disk($this->conversion->file->disk)->path($this->conversion->file->filename));

        $streams = $probe->streams(Storage::disk($this->conversion->file->disk)->path($this->conversion->file->filename));
        foreach ($streams as $stream) {
            if ($stream->get('codec_type') === 'audio') {
                $this->currentAudioBitrate = $stream->get('bit_rate');
                break;
            }
        }
    }

    private function actualDuration()
    {
        $startSeconds = $this->conversion->trim_start;
        $endSeconds = $this->conversion->trim_end;

        if ($startSeconds === null) {
            $startSeconds = 0;
        }

        if ($endSeconds === null) {
            return $this->format->get('duration') - $startSeconds;
        }

        return $endSeconds - $startSeconds;
    }
}
