<?php

namespace Kanata\LaravelBroadcaster\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class BroadcastingMessage implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        public Authenticatable $user,
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('broadcast-action'),
        ];
    }
}
