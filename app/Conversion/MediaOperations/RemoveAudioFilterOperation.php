<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class RemoveAudioFilterOperation implements MediaFilterOperation
{
    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        $media->addFilter('-an');

        return $media;
    }
}
