<?php

namespace App\Events;

use App\Models\AiRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class AiRequestCreated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public AiRequest $aiRequest) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ai-requests.'.$this->aiRequest->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ai.request.created';
    }

    /**
     * The payload can be arbitrarily large (e.g. a full JSON schema for
     * roadmap generation), which easily exceeds Reverb's per-message size
     * limit. Broadcast only the id; the browser fetches the payload over a
     * plain HTTP request that has no such limit.
     *
     * @return array{id: int}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->aiRequest->id,
        ];
    }
}
