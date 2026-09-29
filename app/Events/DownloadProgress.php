<?php

namespace App\Events;

use App\Models\Conversion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DownloadProgress implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public ?string $conversionId;

    public ?string $sessionId;

    public ?string $progressTarget;

    public ?string $percentage;

    public ?string $size;

    public ?string $speed;

    /**
     * Download speed in megabytes (10^6 bytes) per second, null while yt-dlp reports it as unknown.
     */
    public ?float $speedInMegabytes;

    public ?string $eta;

    public string $queue = 'events';

    private ?string $totalTime;

    public function __construct(string $conversionId, ?string $progressTarget = null, ?string $percentage = null, ?string $size = null, ?string $speed = null, ?string $eta = null, ?string $totalTime = null)
    {
        $this->conversionId = $conversionId;
        $conversion = Conversion::find($this->conversionId);
        $this->sessionId = $conversion->session_id;
        $this->progressTarget = $progressTarget;
        $this->percentage = $percentage;
        $this->size = $size;
        $this->speed = $speed;
        $this->speedInMegabytes = self::toMegabytesPerSecond($speed);
        $this->eta = $eta;
        $this->totalTime = $totalTime;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('session.' . $this->sessionId),
        ];
    }

    private static function toMegabytesPerSecond(?string $speed): ?float
    {
        if ($speed === null || preg_match('/^(?<value>\d+(?:\.\d+)?)(?<unit>K|M|G|T)?(?<binary>i)?B\/s$/i', trim($speed), $match) !== 1) {
            return null;
        }

        $base = ($match['binary'] ?? '') !== '' ? 1024 : 1000;
        $exponent = match (strtoupper($match['unit'] ?? '')) {
            'K' => 1,
            'M' => 2,
            'G' => 3,
            'T' => 4,
            default => 0,
        };

        return round((float) $match['value'] * $base ** $exponent / 1_000_000, 2);
    }
}
