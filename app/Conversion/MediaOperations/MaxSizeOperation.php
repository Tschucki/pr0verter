<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFormatOperation;
use App\Models\Conversion;
use FFMpeg\FFProbe;
use FFMpeg\FFProbe\DataMapping\Format;
use FFMpeg\Format\Video\DefaultVideo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MaxSizeOperation implements MediaFormatOperation
{
    public Conversion $conversion;

    private Format $format;

    private $currentAudioBitrate;

    private $currentVideoBitrate;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
        $this->prepareData();
    }

    public function applyToFormat(DefaultVideo $format): DefaultVideo
    {
        $containerOverheadPercent = 0.03;
        $maxSizeInMB = $this->conversion->max_size;
        $usableSize = $maxSizeInMB * (1 - $containerOverheadPercent);

        $duration = $this->actualDuration();

        if ($duration === 0) {
            return $format;
        }

        $audioBitrateKbps = 0;
        if ($this->conversion->audio && $this->currentAudioBitrate) {
            $audioBitrateKbps = ($this->currentAudioBitrate / 1000) * $this->conversion->audio_quality;
        }

        $totalBitrateKbps = ($usableSize * 8192) / $duration;

        $videoBitrateKbps = $totalBitrateKbps - $audioBitrateKbps;

        if ($videoBitrateKbps < 100) {
            $videoBitrateKbps = 100;
        }

        if (isset($this->currentVideoBitrate) && $this->currentVideoBitrate > 0) {
            $originalVideoBitrateKbps = $this->currentVideoBitrate / 1000;
            $videoBitrateKbps = min($videoBitrateKbps, $originalVideoBitrateKbps);
        }

        $kiloBitRate = (int) round($videoBitrateKbps);

        Log::info('Bitrate calculation', [
            'conversion_id' => $this->conversion->id,
            'max_size_mb' => $maxSizeInMB,
            'usable_size_mb' => $usableSize,
            'duration_seconds' => $duration,
            'total_bitrate_kbps' => $totalBitrateKbps,
            'audio_bitrate_kbps' => $audioBitrateKbps,
            'video_bitrate_kbps' => $kiloBitRate,
        ]);

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
            }
            if ($stream->get('codec_type') === 'video') {
                $this->currentVideoBitrate = $stream->get('bit_rate');
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
