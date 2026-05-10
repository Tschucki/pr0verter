<?php

declare(strict_types=1);

namespace App\ValueObjects;

class WeeklyStats
{
    public function __construct(
        public readonly int $totalConversions,
        public readonly int $traffic,
        public readonly ?string $mostUsedInputExtension,
        public readonly int $averageConversionTime,
        public readonly int $addedWatermarks,
        public readonly int $autoCropped,
        public readonly int $trimmed,
        public readonly int $removedAudio,
        public readonly int $audioOnly,
        public readonly int $segmented,
    ) {}
}
