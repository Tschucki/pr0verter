<?php

declare(strict_types=1);

namespace App\Enums;

enum SubtitleMode: string
{
    case None = 'none';
    case Soft = 'soft';
    case Burn = 'burn';
}
