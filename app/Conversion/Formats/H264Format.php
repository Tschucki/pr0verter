<?php

declare(strict_types=1);

namespace App\Conversion\Formats;

use App\Enums\QualityTier;
use FFMpeg\Format\Video\X264;

class H264Format extends X264
{
    private QualityTier $qualityTier;

    public function __construct(QualityTier $qualityTier)
    {
        parent::__construct();
        $this->qualityTier = $qualityTier;

        $this->audioCodec = 'aac';

        $this->setAudioKiloBitrate($qualityTier->getAudioBitrate());
    }

    public function getExtraParams(): array
    {
        return array_merge(parent::getExtraParams(), [
            '-preset', 'medium',
            '-profile:v', 'main',
            '-level', '4.1',
            '-pix_fmt', 'yuv420p',
            '-g', '60',
            '-keyint_min', '30',
            '-movflags', '+faststart',
            '-color_primaries', '1',
            '-color_trc', '1',
            '-colorspace', '1',
        ]);
    }
}
