<?php

namespace App\Events;

use App\Models\Conversion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversionProgressEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $sessionId;

    public string $conversionId;

    public string $percentage;

    public string $remaining;

    public string $rate;

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
        $this->sessionId = Conversion::find($conversionId)->file->session_id;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('session.' . $this->sessionId),
        ];
    }
}
