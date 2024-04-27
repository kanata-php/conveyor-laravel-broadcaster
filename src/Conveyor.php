<?php

namespace Kanata\LaravelBroadcaster;

use Conveyor\SubProtocols\Conveyor\Actions\BroadcastAction;
use Conveyor\SubProtocols\Conveyor\Actions\ChannelConnectAction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Log;
use Exception;
use WebSocket\Client as WsClient;

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
        try {
            foreach ($channels as $channel) {
                $this->sendOneOff($channel, $payload['message']);
            }
        } catch (Exception $e) {
            Log::error('Conveyor: Failed to broadcast to channel: ' . $channel, ['exception' => $e]);
        }
    }

    protected function sendOneOff(string $channel, string $message)
    {
        $protocol = config('conveyor.protocol', 'ws');
        $host = config('conveyor.uri', '127.0.0.1');
        $port = config('conveyor.port', 8002);
        $query = '?' . config('conveyor.query', '');
        $uri = "{$protocol}://{$host}:{$port}/{$query}";

        $client = new WsClient(
            uri: $uri,
            options: [
                'timeout' => 3,
            ],
        );

        // connect to channel
        $channelConnectMessage = json_encode([
            'action' => ChannelConnectAction::NAME,
            'channel' => $channel,
        ]);
        $client->send($channelConnectMessage);

        // broadcast
        $client->send(json_encode([
            'action' => BroadcastAction::NAME,
            'data' => $message,
        ]));
        $client->receive();

        $client->close();
    }
}
