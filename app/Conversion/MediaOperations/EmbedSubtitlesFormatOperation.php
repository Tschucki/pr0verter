<?php

declare(strict_types=1);

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFormatOperation;
use App\Conversion\Formats\H264Format;
use App\Conversion\Formats\H264FormatWithSubs;
use App\Models\Conversion;
use FFMpeg\Format\Video\DefaultVideo;
use Illuminate\Support\Facades\Storage;

final class EmbedSubtitlesFormatOperation implements MediaFormatOperation
{
    public function __construct(private readonly Conversion $conversion) {}

    public function applyToFormat(DefaultVideo $format): DefaultVideo
    {
        if (! $format instanceof H264Format) {
            return $format;
        }

        $subtitlePath = $this->conversion->subtitle_path;
        $absolutePath = str_starts_with($subtitlePath, '/')
            ? $subtitlePath
            : Storage::disk($this->conversion->file->disk)->path($subtitlePath);

        return new H264FormatWithSubs($format->getQualityTier(), $absolutePath);
    }
}
