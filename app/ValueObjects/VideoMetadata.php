<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\AudioCodec;
use App\Enums\QualityTier;
use App\Enums\VideoCodec;

class VideoMetadata
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly float $duration,
        public readonly ?VideoCodec $videoCodec,
        public readonly ?AudioCodec $audioCodec,
        public readonly float $framerate,
        public readonly int $rotation,
        public readonly ?int $audioSampleRate,
    ) {}

    public function getPixelCount(): int
    {
        return $this->width * $this->height;
    }

    public function getQualityTier(): QualityTier
    {
        return QualityTier::fromPixelCount($this->getPixelCount());
    }

    public function isLandscape(): bool
    {
        return $this->width > $this->height;
    }

    public function isPortrait(): bool
    {
        return $this->height > $this->width;
    }

    public function isSquare(): bool
    {
        return $this->width === $this->height;
    }

    public function getAspectRatio(): float
    {
        return $this->width / $this->height;
    }

    public function needsRotation(): bool
    {
        return in_array($this->rotation, [90, 180, 270], true);
    }
}
