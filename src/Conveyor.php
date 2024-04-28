<?php

namespace Kanata\LaravelBroadcaster;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Cache;

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
        Cache::lock('conveyor-messages', 2)
            ->block(6, function () use ($channels, $event, $payload) {
                foreach ($channels as $channel) {
                    $messages = Cache::pull('conveyor-messages', []);
                    array_push($messages, [
                        'channel' => $channel->name,
                        'message' => $payload['message'],
                    ]);
                    Cache::set('conveyor-messages', $messages, 10);
                }
            });
    }
}
