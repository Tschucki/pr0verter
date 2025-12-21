<?php

declare(strict_types=1);

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Enums\QualityTier;
use App\Models\Conversion;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

readonly class ShortEdgeScalingFilterOperation implements MediaFilterOperation
{
    public function __construct(public Conversion $conversion) {}

    public static function calculateScaledDimensions(int $originalWidth, int $originalHeight, QualityTier $tier): array
    {
        $shortEdge = $tier->getShortEdge();

        $isLandscape = $originalWidth > $originalHeight;
        $isPortrait = $originalHeight > $originalWidth;

        if ($isLandscape) {
            // landscape: height is short edge
            $scale = $shortEdge / $originalHeight;
            $newHeight = $shortEdge;
            $newWidth = (int) round($originalWidth * $scale);
        } elseif ($isPortrait) {
            // portrait: width is short edge
            $scale = $shortEdge / $originalWidth;
            $newWidth = $shortEdge;
            $newHeight = (int) round($originalHeight * $scale);
        } else {
            // Square
            $newWidth = $shortEdge;
            $newHeight = $shortEdge;
        }

        $newWidth = $newWidth % 2 === 0 ? $newWidth : $newWidth - 1;
        $newHeight = $newHeight % 2 === 0 ? $newHeight : $newHeight - 1;

        return [
            'width' => $newWidth,
            'height' => $newHeight,
        ];
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        if (! empty($this->conversion->segments)) {
            return $media;
        }

        if (! $this->conversion->quality_tier || ! $this->conversion->metadata) {
            return $media;
        }

        $metadata = $this->conversion->metadata;
        $qualityTier = QualityTier::from($this->conversion->quality_tier);

        $width = $metadata['width'] ?? 0;
        $height = $metadata['height'] ?? 0;
        $rotation = $metadata['rotation'] ?? 0;

        if (in_array($rotation, [90, 270, -90, -270], true)) {
            [$width, $height] = [$height, $width];
        }

        if ($width === 0 || $height === 0) {
            return $media;
        }

        $targetDimensions = self::calculateScaledDimensions($width, $height, $qualityTier);
        $targetWidth = $targetDimensions['width'];
        $targetHeight = $targetDimensions['height'];

        if ($targetWidth > 1920) {
            $scale = 1920 / $targetWidth;
            $targetWidth = 1920;
            $targetHeight = (int) round($targetHeight * $scale);
            $targetHeight = $targetHeight % 2 === 0 ? $targetHeight : $targetHeight - 1;
        }

        $media->addFilter(['-vf', "scale={$targetWidth}:{$targetHeight}:force_original_aspect_ratio=decrease"]);

        return $media;
    }
}
