<?php

namespace Kanara\LaravelBroadcaster;

use Illuminate\Contracts\Broadcasting\Broadcaster;
use Kanata\LaravelBroadcaster\Services\JwtToken;

class Conveyor implements Broadcaster
{
    public function auth($request)
    {
        if (!auth()->check()) {
            return [];
        }

        return $this->validAuthenticationResponse($request);
    }

    public function validAuthenticationResponse($request, $result)
    {
        return [
            'auth' => JwtToken::create(
                name: 'some-random-name',
                userId: auth()->user()->id,
            ),
        ];
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        logger()->info('Broadcast happening!!!', [
            'channels' => $channels,
            'event' => $event,
            'payload' => $payload,
        ]);
    }
}
