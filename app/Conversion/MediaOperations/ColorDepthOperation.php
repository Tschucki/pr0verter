<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFormatOperation;
use App\Models\Conversion;
use FFMpeg\Format\Video\DefaultVideo;

class ColorDepthOperation implements MediaFormatOperation
{
    public Conversion $conversion;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
    }

    public function applyToFormat(DefaultVideo $format): DefaultVideo
    {
        $format->setAdditionalParameters(['-pix_fmt', 'yuv420p']);

        return $format;
    }
}
