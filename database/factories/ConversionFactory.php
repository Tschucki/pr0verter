<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversion;
use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversion>
 */
class ConversionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sessionId = (string) Str::random(40);

        return [
            'session_id' => $sessionId,
            'file_id' => fn () => File::factory()->create(['session_id' => $sessionId])->id,
            'status' => 'pending',
            'audio' => true,
            'audio_only' => false,
            'auto_crop' => false,
            'watermark' => false,
            'interpolation' => false,
            'downloadable' => false,
            'audio_quality' => 0,
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (Conversion $conversion): void {
            if ($conversion->session_id !== null && DB::table('sessions')->where('id', $conversion->session_id)->doesntExist()) {
                self::insertSession($conversion->session_id);
            }
        });
    }

    private static function insertSession(string $sessionId): void
    {
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => '',
            'last_activity' => time(),
        ]);
    }
}
