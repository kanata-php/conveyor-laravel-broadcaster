<?php

namespace Kanata\LaravelBroadcaster;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Log;
use Kanata\ConveyorServerClient\Client;
use Exception;
use WebSocket\TimeoutException;

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
            rescue(
                callback: function () use ($channel, $payload) {
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
                },
                rescue: function (Exception $e) {
                    if ($e instanceof TimeoutException) {
                        return;
                    }
                    Log::info('Conveyor failed to broadcast: ' . $e->getMessage());
                },
                report: false,
            );
        }
    }
}
