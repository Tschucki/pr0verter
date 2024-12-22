<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFProbe;
use FFMpeg\FFProbe\DataMapping\Format;
use FFMpeg\Filters\Video\ClipFilter;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class TrimFilterOperation implements MediaFilterOperation
{
    public Conversion $conversion;

    private Format $format;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
        $this->prepareData();
    }

    public function prepareData(): void
    {
        $probe = app(FFProbe::class);

        $this->format = $probe->format(Storage::disk($this->conversion->file->disk)->path($this->conversion->file->filename));
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        $startSeconds = $this->conversion->trim_start;
        $endSeconds = $this->conversion->trim_end;

        $start = $startSeconds !== null ? TimeCode::fromSeconds($startSeconds) : TimeCode::fromSeconds(0);
        $duration = null;

        if ($endSeconds !== null) {
            $durationSeconds = $endSeconds - $startSeconds;
            $videoDuration = $this->format->get('duration');

            if ($durationSeconds > $videoDuration - $startSeconds) {
                $durationSeconds = $videoDuration - $startSeconds;
            }

            $duration = TimeCode::fromSeconds($durationSeconds);
        }

        $clipFilter = new ClipFilter($start, $duration);
        $media->addFilter($clipFilter);

        return $media;
    }
}
