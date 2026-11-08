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
        // A running job can still update a conversion whose row is gone (new
        // upload in the same session, cleanup, expired session cascade).
        // Eloquent fires "updated" even when no row matched, and recreating the
        // statistic would then violate its foreign key.
        if (! Conversion::whereKey($conversion->getKey())->exists()) {
            return;
        }

        $conversion->trackStatistic();

        ConversionUpdated::dispatch($conversion->id);

        if ($conversion->status === ConversionStatus::FINISHED) {
            ConversionFinished::dispatch($conversion->session_id);
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
