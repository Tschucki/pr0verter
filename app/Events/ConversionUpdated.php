<?php

namespace App\Events;

use App\Models\Conversion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversionUpdated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $sessionId;

    public string $conversionId;

    public array $conversion;

    public string $queue = 'events';

    public function __construct(string $conversionId)
    {
        $this->conversionId = $conversionId;
        $conversion = Conversion::with('file')->where('id', $conversionId)->first();
        $this->conversion = $conversion->toArray();
        $this->sessionId = Conversion::find($conversionId)->session_id;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('session.' . $this->sessionId),
        ];
    }
}
