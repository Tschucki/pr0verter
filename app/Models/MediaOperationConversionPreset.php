<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MediaOperationConversionPreset extends Pivot
{
    use HasUuids;

    protected $guarded = [];

    public $incrementing = true;

    public function conversionPreset(): BelongsTo
    {
        return $this->belongsTo(ConversionPreset::class);
    }

    public function mediaOperation(): BelongsTo
    {
        return $this->belongsTo(MediaOperation::class);
    }
}
