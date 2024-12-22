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
        $url = (config('conveyor.protocol') === 'ws' ? 'http' : 'https') . '://'
            . config('conveyor.uri') . ':'
            . config('conveyor.port') . '/conveyor/message'
            . (config('conveyor.query') ? '?' . config('conveyor.query') : '');

        foreach ($channels as $channel) {
            Http::post($url, [
                'channel' => $channel->name,
                'message' => json_encode($payload),
            ]);
        }
    }

    /**
     * It retrieves a single use token for the private channel.
     */
    public static function getToken(?string $channel = null): string
    {
        $conveyorUrl = (config('conveyor.protocol') === 'ws' ? 'http' : 'https') . '://'
            . config('conveyor.uri') . ':'
            . config('conveyor.port') . '/conveyor/auth'
            . (config('conveyor.query') ? '?' . config('conveyor.query') : '');

        $body = $channel === null ? [] : [
            'channel' => $channel,
        ];

        return Http::post($conveyorUrl, $body)->json('auth', '');
    }
}
