<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class RemoveExifDataFilterOperation implements MediaFilterOperation
{
    public Conversion $conversion;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        $media->addFilter(['-map_metadata', '-1']);

        return $media;
    }
}
