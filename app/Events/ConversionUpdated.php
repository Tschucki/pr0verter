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

    public ?string $sessionId = null;

    public string $conversionId;

    public array $conversion = [];

    public string $queue = 'events';

    public function __construct(string $conversionId)
    {
        $this->conversionId = $conversionId;
        $conversion = Conversion::with('file')->find($conversionId);

        // Null when the conversion was deleted before the event was dispatched.
        if ($conversion !== null) {
            $this->conversion = $conversion->toArray();
            $this->sessionId = $conversion->session_id;
        }
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
