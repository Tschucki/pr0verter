<?php

namespace App\Models;

use App\Enums\ConversionStatus;
use App\Enums\SubtitleMode;
use App\Enums\SubtitleStatus;
use Database\Factories\StatisticFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Statistic extends Model
{
    /** @use HasFactory<StatisticFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'audio' => 'boolean',
        'audio_only' => 'boolean',
        'interpolation' => 'boolean',
        'auto_crop' => 'boolean',
        'max_size' => 'integer',
        'audio_quality' => 'float',
        'watermark' => 'boolean',
        'segments' => 'array',
        'subtitle_mode' => SubtitleMode::class,
        'subtitle_status' => SubtitleStatus::class,
        'status' => ConversionStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    protected static function newFactory(): Factory
    {
        return StatisticFactory::new();
    }
}
