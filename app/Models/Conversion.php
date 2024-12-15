<?php

namespace App\Models;

use Database\Factories\ConversionFactory;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Bus;

/**
 * @property-read Batch|null $batch
 * */
class Conversion extends Model
{
    /** @use HasFactory<ConversionFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $appends = ['batch'];

    public function conversionPreset(): HasOne
    {
        return $this->hasOne(ConversionPreset::class);
    }

    public function file(): HasOne
    {
        return $this->hasOne(File::class);
    }

    public function mediaOperations(): HasManyThrough
    {
        return $this->hasManyThrough(MediaOperation::class, MediaOperationConversionPreset::class)
            ->orderBy('priority', 'desc');
    }

    public function getBatchAttribute(): ?Batch
    {
        return Bus::findBatch($this->job_batch_id);
    }

    public function batchCanceled(): bool
    {
        return $this->batch?->canceled();
    }

    public function batchFailed(): bool
    {
        return $this->batch?->hasFailures();
    }

    public function batchFinished(): bool
    {
        return $this->batch?->finished();
    }
}
