<?php

declare(strict_types=1);

namespace App\Conversion\Formats;

use App\Enums\QualityTier;

final class H264FormatWithSubs extends H264Format
{
    public function __construct(QualityTier $qualityTier, private readonly string $subtitlePath)
    {
        parent::__construct($qualityTier);
    }

    public function getExtraParams(): array
    {
        return array_merge(parent::getExtraParams(), [
            '-i', $this->subtitlePath,
            '-map', '0:v',
            '-map', '0:a?',
            '-map', '1:s',
            '-c:s', 'mov_text',
            '-metadata:s:s:0', 'language=ger',
        ]);
    }
}
