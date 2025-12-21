<?php

declare(strict_types=1);

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class AudioSampleRateLimitFilterOperation implements MediaFilterOperation
{
    private const MAX_SAMPLE_RATE = 48000;

    private const DEFAULT_SAMPLE_RATE = 44100;

    public function __construct(public readonly Conversion $conversion) {}

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        if (! $this->conversion->audio) {
            return $media;
        }

        if (! isset($this->conversion->metadata['audio_sample_rate'])) {
            return $media;
        }

        $sampleRate = (int) $this->conversion->metadata['audio_sample_rate'];

        if ($sampleRate > self::MAX_SAMPLE_RATE) {
            $media->addFilter(['-ar', (string) self::DEFAULT_SAMPLE_RATE]);
        } else {
            $media->addFilter(['-ar', (string) $sampleRate]);
        }

        return $media;
    }
}
