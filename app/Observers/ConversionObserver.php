<?php

namespace App\Observers;

use App\Enums\ConversionStatus;
use App\Events\ConversionFinished;
use App\Events\ConversionUpdated;
use App\Models\Conversion;

class ConversionObserver
{
    public function updated(Conversion $conversion): void
    {
        ConversionUpdated::dispatch($conversion->id);

        if ($conversion->status === ConversionStatus::FINISHED) {
            ConversionFinished::dispatch($conversion->file->session_id);
        }
    }
}
