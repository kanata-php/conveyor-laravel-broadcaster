<?php

namespace Kanata\LaravelBroadcaster;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use WebSocket\Client as WsClient;

class Conveyor
{
    /**
     * @param array<PrivateChannel|Channel> $channels
     * @param $event
     * @param array $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $url = config('conveyor.protocol', 'ws') . '://'
            . config('conveyor.uri', '127.0.0.1') . ':'
            . config('conveyor.port', 8002)
            . (
                !empty(config('conveyor.query')) ?
                    '/?' . config('conveyor.query', '')
                    : ''
            );
        $client = new WsClient($url);

        foreach ($channels as $channel) {
            $client->text(json_encode([
                'action' => 'channel-connect',
                'channel' => $channel->name,
            ]));

            $client->text(json_encode([
                'action' => 'broadcast-action',
                'data' => $payload['message'],
            ]));
        }

        $client->close();
    }
}
