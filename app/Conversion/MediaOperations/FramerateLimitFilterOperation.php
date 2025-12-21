<?php

declare(strict_types=1);

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class FramerateLimitFilterOperation implements MediaFilterOperation
{
    private const MIN_FPS = 24;

    private const MAX_FPS = 60;

    public function __construct(public readonly Conversion $conversion) {}

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        if (! isset($this->conversion->metadata['framerate'])) {
            return $media;
        }

        $framerate = (float) $this->conversion->metadata['framerate'];

        if ($framerate > self::MAX_FPS) {
            $media->addFilter(['-r', (string) self::MAX_FPS]);
        } elseif ($framerate < self::MIN_FPS) {
            $media->addFilter(['-r', (string) self::MIN_FPS]);
        }

        return $media;
    }
}
