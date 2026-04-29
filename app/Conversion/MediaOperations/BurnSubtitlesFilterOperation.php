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
        $subtitlePath = $this->conversion->subtitle_path;
        $absolutePath = str_starts_with($subtitlePath, '/')
            ? $subtitlePath
            : Storage::disk($this->conversion->file->disk)->path($subtitlePath);

        $escaped = str_replace([':', '\\', "'"], ['\\:', '\\\\', "\\'"], $absolutePath);
        $width = (int) data_get($this->conversion->metadata, 'width', 0);
        $height = (int) data_get($this->conversion->metadata, 'height', 0);
        $fontSize = $this->resolveFontSize($width, $height);

        $style = 'FontName=Arial,FontSize=' . $fontSize . ',PrimaryColour=&Hffffff,'
            . 'OutlineColour=&H000000,Outline=0.8,Shadow=0,BorderStyle=1,MarginV=20';

        $expression = "subtitles='{$escaped}':force_style='{$style}'";

        return $media->addFilter(function (VideoFilters $filters) use ($expression): void {
            $filters->custom($expression);
        });
    }

    private function resolveFontSize(int $width, int $height): int
    {
        // libass renders SRT against PlayResY=288 by default, then scales the
        // glyphs by frameHeight/288. So FontSize=N produces roughly
        // N * frameHeight / 288 output pixels — a FontSize of 20 on a 1920px
        // tall portrait video would already be ~133px (way too big).
        //
        // We aim at a target output height of ~3.4% of the shorter side, then
        // back out the FontSize that compensates for libass' 288 → frameHeight
        // scaling. Result: ~6 for 1080×1920 portrait, ~10 for 1280×720 / 1920×1080.
        if ($width <= 0 || $height <= 0) {
            return 10;
        }

        $shortSide = min($width, $height);
        $fontSize = (int) round(0.034 * $shortSide * 288 / $height);

        return max(6, $fontSize);
    }
}
