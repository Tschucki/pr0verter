<?php

namespace App\Contracts;

use FFMpeg\Format\Video\DefaultVideo;

interface MediaFormatOperation
{
    public function applyToFormat(DefaultVideo $format): DefaultVideo;
}
