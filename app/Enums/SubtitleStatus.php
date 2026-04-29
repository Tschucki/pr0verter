<?php

declare(strict_types=1);

namespace App\Enums;

enum SubtitleStatus: string
{
    case Embedded = 'embedded';
    case Burnt = 'burnt';
    case Unavailable = 'unavailable';
}
