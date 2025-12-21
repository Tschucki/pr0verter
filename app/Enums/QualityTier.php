<?php

declare(strict_types=1);

namespace App\Enums;

enum QualityTier: string
{
    case ULTRA_HD = 'ultra_hd';
    case FULL_HD = 'full_hd';
    case HD = 'hd';
    case SD = 'sd';
    case LOW_RES = 'low_res';

    public static function fromPixelCount(int $pixels): self
    {
        return match (true) {
            $pixels > 2_500_000 => self::ULTRA_HD,
            $pixels > 1_500_000 => self::FULL_HD,
            $pixels > 800_000 => self::HD,
            $pixels > 400_000 => self::SD,
            default => self::LOW_RES,
        };
    }

    public function getShortEdge(): int
    {
        return match ($this) {
            self::ULTRA_HD => 2160,
            self::FULL_HD => 1080,
            self::HD => 720,
            self::SD => 480,
            self::LOW_RES => 360,
        };
    }

    public function getH264MaxBitrate(): int
    {
        return match ($this) {
            self::ULTRA_HD => 12000,
            self::FULL_HD => 8000,
            self::HD => 5000,
            self::SD => 2500,
            self::LOW_RES => 1000,
        };
    }

    public function getAudioBitrate(): int
    {
        return match ($this) {
            self::ULTRA_HD => 224,
            self::FULL_HD => 192,
            self::HD => 128,
            self::SD => 96,
            self::LOW_RES => 64,
        };
    }
}
