<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConversionStatus;
use App\Models\Statistic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Statistic>
 */
class StatisticFactory extends Factory
{
    protected $model = Statistic::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'status' => ConversionStatus::FINISHED,
            'audio' => true,
            'audio_only' => false,
            'auto_crop' => false,
            'watermark' => false,
            'interpolation' => false,
            'audio_quality' => 0.0,
            'trim_start' => null,
            'trim_end' => null,
            'max_size' => null,
            'url' => null,
            'size' => $this->faker->numberBetween(1_000_000, 200_000_000),
            'segments' => null,
            'conversion_started_at' => now()->subSeconds(60),
            'conversion_ended_at' => now()->subSeconds(50),
        ];
    }

    public function youtube(): self
    {
        return $this->state(fn () => ['url' => 'https://youtube.com/watch?v=' . $this->faker->lexify('????????')]);
    }

    public function watermarked(): self
    {
        return $this->state(fn () => ['watermark' => true]);
    }

    public function autoCropped(): self
    {
        return $this->state(fn () => ['auto_crop' => true]);
    }

    public function trimmed(): self
    {
        return $this->state(fn () => [
            'trim_start' => 5,
            'trim_end' => 30,
        ]);
    }

    public function audioOnly(): self
    {
        return $this->state(fn () => ['audio_only' => true]);
    }

    public function noAudio(): self
    {
        return $this->state(fn () => ['audio' => false]);
    }

    public function segmented(): self
    {
        return $this->state(fn () => ['segments' => [[0, 10], [20, 30]]]);
    }

    public function failed(): self
    {
        return $this->state(fn () => ['status' => ConversionStatus::FAILED]);
    }
}
