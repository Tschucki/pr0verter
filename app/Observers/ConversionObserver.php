<?php

namespace App\Observers;

use App\Enums\ConversionStatus;
use App\Events\ConversionFinished;
use App\Events\ConversionUpdated;
use App\Jobs\DownloadVideoJob;
use App\Models\Conversion;

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
            //DownloadVideoJob::dispatchSync($conversion->id);
            DownloadVideoJob::dispatch($conversion->id)->onQueue('downloader');
        }
    }

    public function deleting(Conversion $conversion): void
    {
        $conversion->trackStatistic();
    }
}
