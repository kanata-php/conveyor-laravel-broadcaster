<?php

namespace Kanata\LaravelBroadcaster;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Http;

class Conveyor
{
    /**
     * @param array<PrivateChannel|Channel> $channels
     * @param $event
     * @param array $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        foreach ($channels as $channel) {
            Http::post(config('app.url') . '/conveyor/message', [
                'channel' => $channel->name,
                'message' => json_encode($payload),
            ]);
        }
    }
}
