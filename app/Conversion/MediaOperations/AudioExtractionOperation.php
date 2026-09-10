<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFormatOperation;
use App\Models\Conversion;
use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Mp3;

class AudioExtractionOperation implements MediaFormatOperation
{
    private Conversion $conversion;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
    }

    public function applyToFormat(DefaultAudio $format): DefaultAudio
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
