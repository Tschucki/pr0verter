<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Statistic extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'audio' => 'boolean',
        'interpolation' => 'boolean',
        'auto_crop' => 'boolean',
        'max_size' => 'integer',
        'audio_quality' => 'float',
        'watermark' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }
}
