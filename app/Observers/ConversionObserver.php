<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ConversionStatus;
use App\Events\ConversionFinished;
use App\Events\ConversionUpdated;
use App\Jobs\DownloadVideoJob;
use App\Models\Conversion;
use App\Services\ThumbnailService;

class ConversionObserver
{
    public function updated(Conversion $conversion): void
    {
        $conversion->trackStatistic();

        ConversionUpdated::dispatch($conversion->id);

        if ($conversion->status === ConversionStatus::FINISHED) {
            ConversionFinished::dispatch($conversion->file->session_id);
        }
    }

    public function created(Conversion $conversion): void
    {
        $conversion->trackStatistic();

        if ($conversion->status === ConversionStatus::PENDING && $conversion->url !== null && $conversion->file_id === null) {
            DownloadVideoJob::dispatch($conversion->id);
        }
    }

    public function deleting(Conversion $conversion): void
    {
        $conversion->trackStatistic();

        app(ThumbnailService::class)->delete($conversion);
    }
}
