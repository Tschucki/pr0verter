<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFormatOperation;
use App\Models\Conversion;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\DefaultVideo;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AudioQualityFilterOperation implements MediaFormatOperation
{
    public Conversion $conversion;

    private int $currentBitrate;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
        $this->prepareData();
    }

    public function applyToFormat(DefaultVideo $format): DefaultVideo
    {
        $maxQuality = $this->conversion->audio_quality;

        if ($maxQuality < 0 || $maxQuality > 1) {
            throw new RuntimeException('Invalid audio quality value. Must be between 0 and 1');
        }

        if ($maxQuality === 1) {
            return $format;
        }

        $audioBitrate = $this->conversion->audio ? $this->currentBitrate * $this->conversion->audio_quality : 0;
        $audioBitrateInKiloBits = floor($audioBitrate / 1024);

        $format->setAudioKiloBitrate($audioBitrateInKiloBits);

        return $format;
    }

    private function prepareData(): void
    {
        $probe = app(FFProbe::class);

        $streams = $probe->streams(Storage::disk($this->conversion->file->disk)->path($this->conversion->file->filename));
        foreach ($streams as $stream) {
            if ($stream->get('codec_type') === 'audio') {
                $this->currentBitrate = $stream->get('bit_rate');
                break;
            }
        }
    }
}
