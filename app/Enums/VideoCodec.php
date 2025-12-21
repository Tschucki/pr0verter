<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoCodec: string
{
    case H264 = 'h264';
    case VP9 = 'vp9';
    case VP8 = 'vp8';
    case HEVC = 'hevc';
    case AV1 = 'av1';

    public function isSupported(): bool
    {
        return in_array($this, [self::H264, self::VP9, self::VP8, self::HEVC, self::AV1], true);
    }
}
