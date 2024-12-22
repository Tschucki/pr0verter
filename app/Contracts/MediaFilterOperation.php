<?php

namespace App\Contracts;

use ProtoneMedia\LaravelFFMpeg\MediaOpener;

interface MediaFilterOperation
{
    public function applyToMedia(MediaOpener $media): MediaOpener;
}
