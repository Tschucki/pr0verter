<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class FastStartFilterOperation implements MediaFilterOperation
{
    public Conversion $conversion;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        $media->addFilter(['-movflags', 'faststart']);

        return $media;
    }
}
