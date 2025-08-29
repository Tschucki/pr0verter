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
        $this->eta = $eta;
        $this->totalTime = $totalTime;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('session.' . $this->sessionId),
        ];
    }
}
