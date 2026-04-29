<?php

declare(strict_types=1);

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use FFMpeg\Filters\Video\VideoFilters;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

final class BurnSubtitlesFilterOperation implements MediaFilterOperation
{
    public function __construct(private readonly Conversion $conversion) {}

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        $absolutePath = Storage::disk($this->conversion->file->disk)
            ->path($this->conversion->subtitle_path);

        $escaped = str_replace([':', '\\', "'"], ['\\:', '\\\\', "\\'"], $absolutePath);

        $style = 'FontName=Arial,FontSize=22,PrimaryColour=&Hffffff,'
            . 'OutlineColour=&H000000,Outline=1.5,BorderStyle=1';

        $expression = "subtitles='{$escaped}':force_style='{$style}'";

        return $media->addFilter(function (VideoFilters $filters) use ($expression): void {
            $filters->custom($expression);
        });
    }
}
