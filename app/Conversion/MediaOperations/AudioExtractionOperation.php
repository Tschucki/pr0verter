<?php

namespace App\Conversion\MediaOperations;

use App\Models\Conversion;
use FFMpeg\Format\Audio\Mp3;

class AudioExtractionOperation
{
    private Conversion $conversion;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
    }

    public function applyToFormat($format)
    {
        if ($this->conversion->audio_only) {
            return new Mp3;
        }

        return $format;
    }

    public function supportsAudioOnly(): bool
    {
        return true;
    }
}
