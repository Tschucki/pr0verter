<?php

declare(strict_types=1);

namespace App\Enums;

enum AudioCodec: string
{
    case AAC = 'aac';
    case OPUS = 'opus';
    case VORBIS = 'vorbis';
    case MP3 = 'mp3';

    public function isSupported(): bool
    {
        return true;
    }
}
