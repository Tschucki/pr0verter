<?php

namespace App\Models;

use Database\Factories\MediaOperationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MediaOperation extends Model
{
    /** @use HasFactory<MediaOperationFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function conversionPresets(): BelongsToMany
    {
        return $this->belongsToMany(ConversionPreset::class, 'media_operation_conversion_preset')
            ->withTimestamps()
            ->using(MediaOperationConversionPreset::class);
    }
}
