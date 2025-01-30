<?php

namespace App\Models;

use App\Contracts\MediaFilterOperation;
use App\Contracts\MediaFormatOperation;
use App\Conversion\MediaOperations\AddPr0GrammWatermarkFilterOperation;
use App\Conversion\MediaOperations\AudioQualityFilterOperation;
use App\Conversion\MediaOperations\AutoCropFilterOperation;
use App\Conversion\MediaOperations\InterpolateFilterOperation;
use App\Conversion\MediaOperations\MaxSizeOperation;
use App\Conversion\MediaOperations\RemoveAudioFilterOperation;
use App\Conversion\MediaOperations\TrimFilterOperation;
use App\Enums\ConversionStatus;
use App\Observers\ConversionObserver;
use Database\Factories\ConversionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(ConversionObserver::class)]
class Conversion extends Model
{
    /** @use HasFactory<ConversionFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'audio' => 'boolean',
        'interpolation' => 'boolean',
        'auto_crop' => 'boolean',
        'max_size' => 'integer',
        'audio_quality' => 'float',
        'watermark' => 'boolean',
        'downloadable' => 'boolean',
    ];

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['progress'] = $this->getProgress();
        $array['public'] = $this->getPublicAttribute();

        return $array;
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function getProgress(): array
    {
        return [
            [
                'order' => 1,
                'completed' => $this->status !== ConversionStatus::PENDING,
                'current_step' => $this->status === ConversionStatus::PENDING,
                'status' => ConversionStatus::PENDING,
                'title' => 'Warte auf Worker',
                'description' => 'Es wird auf einen freien Worker gewartet.',
                'visible' => true,
            ],
            [
                'order' => 2,
                'completed' => $this->status === ConversionStatus::PROCESSING || $this->status === ConversionStatus::PREPARING || $this->status === ConversionStatus::FINISHED || $this->status === ConversionStatus::FAILED || $this->status === ConversionStatus::CANCELED,
                'current_step' => $this->status === ConversionStatus::DOWNLOADING,
                'status' => ConversionStatus::DOWNLOADING,
                'title' => 'Download läuft',
                'description' => 'Es wird versucht das Video herunterzuladen.',
                'visible' => $this->url !== null,
            ],
            [
                'order' => 3,
                'completed' => $this->status === ConversionStatus::PROCESSING || $this->status === ConversionStatus::FINISHED || $this->status === ConversionStatus::FAILED || $this->status === ConversionStatus::CANCELED,
                'current_step' => $this->status === ConversionStatus::PREPARING,
                'status' => ConversionStatus::PREPARING,
                'title' => 'Vorbereitungen',
                'description' => 'Die Konvertierung wird vorbereitet. Berechnung von Bitrates und anderen Einstellungen.',
                'visible' => true,
            ],
            [
                'order' => 4,
                'completed' => $this->status === ConversionStatus::FINISHED || $this->status === ConversionStatus::FAILED || $this->status === ConversionStatus::CANCELED,
                'current_step' => $this->status === ConversionStatus::PROCESSING,
                'status' => ConversionStatus::PROCESSING,
                'title' => 'Konvertierung läuft',
                'description' => 'Das Video wird verarbeitet.',
                'visible' => true,
            ],
            [
                'order' => 5,
                'completed' => $this->status === ConversionStatus::FINISHED,
                'current_step' => $this->status === ConversionStatus::FINISHED,
                'status' => ConversionStatus::FINISHED,
                'title' => 'Konvertierung abgeschlossen',
                'description' => 'Das Video wurde erfolgreich konvertiert und kann heruntergeladen werden.',
                'visible' => $this->status !== 'cancelled' && $this->status !== ConversionStatus::FAILED,
            ],
            [
                'order' => 6,
                'completed' => $this->status === ConversionStatus::FAILED,
                'current_step' => $this->status === ConversionStatus::FAILED,
                'status' => ConversionStatus::FAILED,
                'title' => 'Konvertierung fehlgeschlagen',
                'description' => 'Die Konvertierung ist fehlgeschlagen.',
                'visible' => $this->status === ConversionStatus::FAILED,
            ],
            [
                'order' => 7,
                'completed' => $this->status === ConversionStatus::CANCELED,
                'current_step' => $this->status === ConversionStatus::CANCELED,
                'status' => ConversionStatus::CANCELED,
                'title' => 'Konvertierung abgebrochen',
                'description' => 'Die Konvertierung wurde abgebrochen.',
                'visible' => $this->status === ConversionStatus::CANCELED,
            ],
        ];
    }

    /**
     * @return MediaFilterOperation[]
     * */
    public function getMediaOperations(): array
    {
        $operations = [];

        if ($this->audio === false) {
            $operations[] = new RemoveAudioFilterOperation;
        }

        if ($this->trim_start || $this->trim_end) {
            $operations[] = new TrimFilterOperation($this);
        }

        if ($this->auto_crop) {
            $operations[] = new AutoCropFilterOperation($this);
        }

        if ($this->watermark) {
            $operations[] = new AddPr0GrammWatermarkFilterOperation($this);
        }

        if ($this->interpolation) {
            $operations[] = new InterpolateFilterOperation($this);
        }

        foreach (config('converter.default_operations') as $operation) {
            $operations[] = new $operation($this);
        }

        return $operations;
    }

    /**
     * @return MediaFormatOperation[]
     * */
    public function getFormatOperations(): array
    {
        $operations = [];

        if ($this->max_size) {
            $operations[] = new MaxSizeOperation($this);
        }

        if ($this->audio_quality) {
            $operations[] = new AudioQualityFilterOperation($this);
        }

        foreach (config('converter.default_format_operations') as $operation) {
            $operations[] = new $operation($this);
        }

        return $operations;
    }

    public function statistic(): BelongsTo
    {
        return $this->belongsTo(Statistic::class);
    }

    public function trackStatistic(): void
    {
        $conversionEnd = $this->status === ConversionStatus::FINISHED ? now() : null;

        $updateValues = [
            'mime_type' => $this->file->mime_type ?? '',
            'extension' => $this->file->extension ?? '',
            'size' => $this->file->size ?? 0,
            'status' => $this->status,
            'keep_resolution' => $this->keep_resolution,
            'audio' => $this->audio,
            'auto_crop' => $this->auto_crop,
            'watermark' => $this->watermark,
            'interpolation' => $this->interpolation,
            'audio_quality' => $this->audio_quality,
            'trim_start' => $this->trim_start,
            'trim_end' => $this->trim_end,
            'max_size' => $this->max_size,
            'url' => $this->url,
            'conversion_started_at' => $this->created_at,
            'conversion_ended_at' => $conversionEnd,
        ];

        $stat = Statistic::where('conversion_id', $this->id)->first();

        if ($stat) {
            if ($stat->mime_type !== '' && $stat->mime_type !== $updateValues['mime_type']) {
                $updateValues['mime_type'] = $stat->mime_type;
            }
            if ($stat->extension !== '' && $stat->extension !== $updateValues['extension']) {
                $updateValues['extension'] = $stat->extension;
            }
            if ($stat->size !== 0 && $stat->size !== $updateValues['size']) {
                $updateValues['size'] = $stat->size;
            }
            if ($stat->size === 0) {
                $updateValues['size'] = $this->file->size ?? 0;
            }

            $stat->update($updateValues);
        } else {
            Statistic::create(array_merge($updateValues, ['conversion_id' => $this->id]));
        }
    }

    public function getPublicAttribute(): bool
    {
        return (bool)$this->file?->isPublic();
    }
}
