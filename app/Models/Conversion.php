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
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'audio' => 'boolean',
        'downloadable' => 'boolean',
    ];

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['progress'] = $this->getProgress();

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
                'completed' => $this->status === ConversionStatus::PROCESSING || $this->status === ConversionStatus::FINISHED || $this->status === ConversionStatus::FAILED || $this->status === ConversionStatus::CANCELED,
                'current_step' => $this->status === ConversionStatus::PREPARING,
                'status' => ConversionStatus::PREPARING,
                'title' => 'Vorbereitungen',
                'description' => 'Die Konvertierung wird vorbereitet. Berechnung von Bitrates und anderen Einstellungen.',
                'visible' => true,
            ],
            [
                'order' => 3,
                'completed' => $this->status === ConversionStatus::FINISHED || $this->status === ConversionStatus::FAILED || $this->status === ConversionStatus::CANCELED,
                'current_step' => $this->status === ConversionStatus::PROCESSING,
                'status' => ConversionStatus::PROCESSING,
                'title' => 'Konvertierung läuft',
                'description' => 'Das Video wird verarbeitet.',
                'visible' => true,
            ],
            [
                'order' => 4,
                'completed' => $this->status === ConversionStatus::FINISHED,
                'current_step' => $this->status === ConversionStatus::FINISHED,
                'status' => ConversionStatus::FINISHED,
                'title' => 'Konvertierung abgeschlossen',
                'description' => 'Das Video wurde erfolgreich konvertiert und kann heruntergeladen werden.',
                'visible' => $this->status !== 'cancelled' && $this->status !== ConversionStatus::FAILED,
            ],
            [
                'order' => 5,
                'completed' => $this->status === ConversionStatus::FAILED,
                'current_step' => $this->status === ConversionStatus::FAILED,
                'status' => ConversionStatus::FAILED,
                'title' => 'Konvertierung fehlgeschlagen',
                'description' => 'Die Konvertierung ist fehlgeschlagen.',
                'visible' => $this->status === ConversionStatus::FAILED,
            ],
            [
                'order' => 6,
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
}
