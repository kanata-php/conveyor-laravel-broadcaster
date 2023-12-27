<?php

namespace Kanata\LaravelBroadcaster;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Kanata\ConveyorServerClient\Client;

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
        foreach ($channels as $channel) {
            $options = [
                'protocol' => config('conveyor.protocol', 'ws'),
                'host' => config('conveyor.uri', '127.0.0.1'),
                'port' => config('conveyor.port', 8002),
                'query' => '?'.config('conveyor.query', ''),
                'channel' => $channel->name,
                'timeout' => 1,
                'onReadyCallback' => function(Client $currentClient) use ($payload) {
                    $currentClient->send($payload['message']);
                },
            ];
            $client = new Client($options);
            $client->connect();
        }
    }
}
