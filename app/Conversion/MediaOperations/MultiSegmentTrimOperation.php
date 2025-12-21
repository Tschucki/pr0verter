<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Enums\QualityTier;
use App\Models\Conversion;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class MultiSegmentTrimOperation implements MediaFilterOperation
{
    public Conversion $conversion;

    public array $segments;

    public function __construct(Conversion $conversion, array $segments)
    {
        $this->conversion = $conversion;
        $this->segments = $segments;
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        if (empty($this->segments)) {
            return $media;
        }

        $filterComplex = [];
        $videoLabels = [];
        $audioLabels = [];

        $needsScaling = false;
        $targetWidth = null;
        $targetHeight = null;

        if ($this->conversion->quality_tier && $this->conversion->metadata) {
            $metadata = $this->conversion->metadata;
            $qualityTier = QualityTier::from($this->conversion->quality_tier);

            $width = $metadata['width'] ?? 0;
            $height = $metadata['height'] ?? 0;
            $rotation = $metadata['rotation'] ?? 0;

            if (in_array($rotation, [90, 270, -90, -270], true)) {
                [$width, $height] = [$height, $width];
            }

            if ($width > 0 && $height > 0) {
                $dimensions = ShortEdgeScalingFilterOperation::calculateScaledDimensions($width, $height, $qualityTier);
                $targetWidth = $dimensions['width'];
                $targetHeight = $dimensions['height'];

                if ($targetWidth > 1920) {
                    $scale = 1920 / $targetWidth;
                    $targetWidth = 1920;
                    $targetHeight = (int) round($targetHeight * $scale);
                    $targetHeight = $targetHeight % 2 === 0 ? $targetHeight : $targetHeight - 1;
                }

                if ($targetWidth !== $width || $targetHeight !== $height) {
                    $needsScaling = true;
                }
            }
        }

        $needsWatermark = $this->conversion->watermark;
        $watermarkOffsetX = 0;
        $watermarkOffsetY = 0;

        if ($needsWatermark) {
            $finalWidth = $needsScaling ? $targetWidth : ($this->conversion->metadata['width'] ?? 1920);
            $finalHeight = $needsScaling ? $targetHeight : ($this->conversion->metadata['height'] ?? 1080);

            $watermarkOffsetX = (int) round($finalWidth * 0.05);
            $watermarkOffsetY = (int) round($finalHeight * 0.05);
        }

        foreach ($this->segments as $index => $segment) {
            $start = $segment['start'] ?? 0;
            $duration = $segment['duration'] ?? null;

            $videoFilter = "[0:v]trim=start={$start}";
            if ($duration !== null) {
                $videoFilter .= ":duration={$duration}";
            }
            $videoFilter .= ',setpts=PTS-STARTPTS,fps=fps=25';

            if ($needsScaling) {
                $videoFilter .= ",scale={$targetWidth}:{$targetHeight}:force_original_aspect_ratio=decrease";
            }

            $videoFilter .= "[v{$index}]";

            $filterComplex[] = $videoFilter;
            $videoLabels[] = "[v{$index}]";

            // Audio segment
            $audioFilter = "[0:a]atrim=start={$start}";
            if ($duration !== null) {
                $audioFilter .= ":duration={$duration}";
            }
            $audioFilter .= ",asetpts=PTS-STARTPTS[a{$index}]";
            $filterComplex[] = $audioFilter;
            $audioLabels[] = "[a{$index}]";
        }

        // Concatenate
        $filterComplex[] = implode('', $videoLabels) . 'concat=n=' . count($this->segments) . ':v=1:a=0:unsafe=1[concatv]';
        $filterComplex[] = implode('', $audioLabels) . 'concat=n=' . count($this->segments) . ':v=0:a=1:unsafe=1[outa]';

        if ($needsWatermark) {
            $watermarkPath = storage_path('app/watermarks/pr0gramm-logo.png');
            $watermarkPath = str_replace(':', '\\:', $watermarkPath);

            $filterComplex[] = "movie='{$watermarkPath}'[watermark]";
            $filterComplex[] = "[concatv][watermark]overlay=main_w-{$watermarkOffsetX}-overlay_w:main_h-{$watermarkOffsetY}-overlay_h[outv]";
        } else {
            $filterComplex[] = "[concatv]copy[outv]";
        }

        $media->addFilter(['-filter_complex', implode(';', $filterComplex)]);
        $media->addFilter(['-map', '[outv]']);
        $media->addFilter(['-map', '[outa]']);

        return $media;
    }
}
