<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => fn () => $this->createSession(),
            'filename' => 'sample.mp4',
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'size' => 0,
            'disk' => 'conversions',
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (File $file): void {
            if ($file->session_id !== null && DB::table('sessions')->where('id', $file->session_id)->doesntExist()) {
                $this->insertSession($file->session_id);
            }
        });
    }

    private function createSession(): string
    {
        $sessionId = (string) Str::random(40);
        $this->insertSession($sessionId);

        return $sessionId;
    }

    private function insertSession(string $sessionId): void
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
