<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use FFMpeg\Filters\Video\VideoFilters;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class InterpolateFilterOperation implements MediaFilterOperation
{
    public Conversion $conversion;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        $media->addFilter(function (VideoFilters $filters) {
            $filters->custom('minterpolate=fps=60');
        });

        return $media;
    }
}
