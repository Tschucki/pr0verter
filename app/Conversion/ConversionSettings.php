<?php

namespace App\Conversion;

use App\Models\Conversion;
use Illuminate\Support\Str;
use JsonException;

class ConversionSettings
{
    public bool $audio;

    public bool $keepResolution;

    public float $audioQuality;

    public ?int $trimStart;

    public ?int $trimEnd;

    public ?int $maxSize;

    public bool $autoCrop;

    public bool $watermark;

    public bool $interpolation;

    public function __construct(array $settings = [])
    {
        $this->fromArray($settings);
    }

    public function fromArray(array $settings): void
    {
        $settings = $this->convertKeysToSnakeCase($settings);

        $this->audio = $settings['audio'] ?? true;
        $this->keepResolution = $settings['keep_resolution'] ?? false;
        $this->audioQuality = $settings['audio_quality'] ?? 1.0;
        $this->trimStart = $settings['trim_start'] ?? null;
        $this->trimEnd = $settings['trim_end'] ?? null;
        $this->maxSize = $settings['max_size'] ?? null;
        $this->autoCrop = $settings['auto_crop'] ?? false;
        $this->watermark = $settings['watermark'] ?? false;
        $this->interpolation = $settings['interpolation'] ?? false;
    }

    public static function fromConversion(Conversion $conversion): ConversionSettings
    {
        return new ConversionSettings($conversion->toArray());
    }

    public function toArray(): array
    {
        return [
            'audio' => $this->audio,
            'keep_resolution' => $this->keepResolution,
            'audio_quality' => $this->audioQuality,
            'trim_start' => $this->trimStart,
            'trim_end' => $this->trimEnd,
            'max_size' => $this->maxSize,
            'auto_crop' => $this->autoCrop,
            'watermark' => $this->watermark,
            'interpolation' => $this->interpolation,
        ];
    }

    private function convertKeysToSnakeCase(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $result[Str::snake($key)] = $value;
        }

        return $result;
    }

    /**
     * @throws JsonException
     */
    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public static function fromString(string $settings): ConversionSettings
    {
        return new ConversionSettings(json_decode($settings, true, 512, JSON_THROW_ON_ERROR));
    }

    public static function fromRequest(array $settings): ConversionSettings
    {
        return new ConversionSettings($settings);
    }
}
