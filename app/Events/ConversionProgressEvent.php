<?php

namespace App\Events;

use App\Models\Conversion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversionProgressEvent implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public ?string $sessionId;

    public string $conversionId;

    public string $percentage;

    public string $remaining;

    public string $rate;

    public string $queue = 'events';

    public function __construct(
        string $conversionId,
        string $percentage,
        string $remaining,
        string $rate
    ) {
        $this->conversionId = $conversionId;
        $this->percentage = $percentage;
        $this->remaining = $remaining;
        $this->rate = $rate;
        // Null when the conversion was deleted while the encoding was still running.
        $this->sessionId = Conversion::whereKey($conversionId)->value('session_id');
    }

    public function broadcastWhen(): bool
    {
        return $this->sessionId !== null;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('session.' . $this->sessionId),
        ];
    }
}
