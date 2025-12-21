<?php

declare(strict_types=1);

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

readonly class RotationFilterOperation implements MediaFilterOperation
{
    public function __construct(public Conversion $conversion) {}

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        $media->addFilter(['-noautorotate']);

        if ($this->conversion->metadata && isset($this->conversion->metadata['rotation'])) {
            $rotation = (int) $this->conversion->metadata['rotation'];

            if ($rotation === 90) {
                $media->addFilter(['-vf', 'transpose=1']);
            } elseif ($rotation === 180) {
                $media->addFilter(['-vf', 'transpose=2,transpose=2']);
            } elseif ($rotation === 270) {
                $media->addFilter(['-vf', 'transpose=2']);
            }
        }

        return $media;
    }
}
