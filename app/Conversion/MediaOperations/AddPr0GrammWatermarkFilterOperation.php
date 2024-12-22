<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use FFMpeg\FFProbe;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Filters\WatermarkFactory;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class AddPr0GrammWatermarkFilterOperation implements MediaFilterOperation
{
    public Conversion $conversion;

    private float $offsetY;

    private float $offsetX;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
        $this->prepareData();
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        return $media->addWatermark(function (WatermarkFactory $watermark) {
            $watermark->fromDisk('watermarks')
                ->open('pr0gramm-logo.png')
                ->right((int) $this->offsetX)
                ->bottom((int) $this->offsetY);
        });
    }

    private function prepareData(): void
    {
        $probe = app(FFProbe::class);
        $streams = $probe->streams(Storage::disk($this->conversion->file->disk)->path($this->conversion->file->filename));
        $videoStream = $streams->videos()->first();

        if (! $videoStream) {
            return;
        }

        $this->offsetX = $videoStream->get('width') * 0.05;
        $this->offsetY = $videoStream->get('height') * 0.05;
    }
}
