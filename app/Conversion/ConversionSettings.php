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

    public array $segments;

    public function __construct(array $settings = [])
    {
        $this->fromArray($settings);
    }

    /**
     * @throws JsonException
     */
    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public static function fromConversion(Conversion $conversion): ConversionSettings
    {
        return new ConversionSettings($conversion->toArray());
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

    public function fromArray(array $settings): void
    {
        $settings = $this->convertKeysToSnakeCase($settings);

        $this->audio = $settings['audio'] ?? true;
        $this->keepResolution = $settings['keep_resolution'] ?? false;
        $this->audioQuality = $settings['audio_quality'] ?? 1.0;
        $this->trimStart = $this->convertToSeconds($settings['trim_start'] ?? null);
        $this->trimEnd = $this->convertToSeconds($settings['trim_end'] ?? null);
        $this->maxSize = $settings['max_size'] ?? null;
        $this->autoCrop = $settings['auto_crop'] ?? false;
        $this->watermark = $settings['watermark'] ?? false;
        $this->interpolation = $settings['interpolation'] ?? false;
        $this->segments = $settings['segments'] ?? [];

        if (count($this->segments) > 0) {
            $this->trimEnd = null;
            $this->trimStart = null;
        }
    }

    private function convertToSeconds(?string $time): ?int
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        $time = preg_replace('/\s+/', '', $time);
        $time = preg_replace('/[.,\-]/', ':', $time);

        if (! str_contains($time, ':')) {
            return filter_var($time, FILTER_VALIDATE_INT) !== false ? (int) $time : null;
        }

        $parts = explode(':', $time);

        $parts = array_filter($parts, static fn ($part) => $part !== '');

        foreach ($parts as $part) {
            if (! is_numeric($part)) {
                return null;
            }
        }

        $parts = array_values(array_map('intval', $parts));

        return match (count($parts)) {
            1 => $parts[0],

            2 => (
                $parts[0] >= 0 &&
                $parts[1] >= 0 &&
                $parts[1] < 60
            )
                ? $parts[0] * 60 + $parts[1]
                : null,

            3 => (
                $parts[0] >= 0 &&
                $parts[1] >= 0 &&
                $parts[2] >= 0 &&
                $parts[1] < 60 &&
                $parts[2] < 60
            )
                ? $parts[0] * 3600 + $parts[1] * 60 + $parts[2]
                : null,

            default => null
        };
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
            'segments' => $this->segments,
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
}
