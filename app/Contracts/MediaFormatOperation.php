<?php

namespace App\Contracts;

use FFMpeg\Format\Audio\DefaultAudio;

interface MediaFormatOperation
{
    /**
     * DefaultAudio is the common base of the video formats (X264 and friends)
     * and Mp3, so audio-only conversions fit through here as well. Operations
     * that touch video settings must check for DefaultVideo themselves.
     */
    public function applyToFormat(DefaultAudio $format): DefaultAudio;
}
