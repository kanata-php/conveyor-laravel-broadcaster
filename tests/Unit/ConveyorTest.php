<?php

namespace Kanata\LaravelBroadcaster\Tests\Unit;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Kanata\LaravelBroadcaster\Conveyor;
use Kanata\LaravelBroadcaster\Tests\TestCase;

class ConveyorTest extends TestCase
{
    public function test_broadcast_posts_to_each_channel_on_ws_protocol(): void
    {
        Config::set('conveyor.protocol', 'ws');
        Config::set('conveyor.uri', '127.0.0.1');
        Config::set('conveyor.port', 8002);
        Config::set('conveyor.query', '');
        Http::fake();

        $conveyor = new Conveyor();
        $conveyor->broadcast(
            [new Channel('one'), new PrivateChannel('two')],
            'event-x',
            ['a' => 1],
        );

        Http::assertSentCount(2);
        Http::assertSent(function (Request $req) {
            return $req->url() === 'http://127.0.0.1:8002/conveyor/message'
                && $req['channel'] === 'one'
                && $req['message'] === json_encode(['a' => 1]);
        });
        Http::assertSent(function (Request $req) {
            return $req['channel'] === 'private-two';
        });
    }

    public function test_broadcast_uses_https_when_protocol_is_not_ws(): void
    {
        Config::set('conveyor.protocol', 'wss');
        Config::set('conveyor.uri', 'example.test');
        Config::set('conveyor.port', 8443);
        Config::set('conveyor.query', 'token=abc');
        Http::fake();

        (new Conveyor())->broadcast([new Channel('c')], 'evt');

        Http::assertSent(function (Request $req) {
            return str_starts_with($req->url(), 'https://example.test:8443/conveyor/message')
                && str_contains($req->url(), 'token=abc');
        });
    }

    public function test_get_token_returns_empty_when_query_config_missing(): void
    {
        Config::set('conveyor.query', '');

        $this->assertSame('', Conveyor::getToken('chan'));
    }

    public function test_get_token_posts_to_auth_endpoint_with_channel(): void
    {
        Config::set('conveyor.protocol', 'ws');
        Config::set('conveyor.uri', '127.0.0.1');
        Config::set('conveyor.port', 8002);
        Config::set('conveyor.query', 'k=v');
        Http::fake([
            'http://127.0.0.1:8002/conveyor/auth*' => Http::response(['auth' => 'jwt-string'], 200),
        ]);

        $token = Conveyor::getToken('private-room');

        $this->assertSame('jwt-string', $token);
        Http::assertSent(fn (Request $req) => $req['channel'] === 'private-room');
    }

    public function test_get_token_omits_channel_when_null(): void
    {
        Config::set('conveyor.query', 'k=v');
        Http::fake(['*' => Http::response(['auth' => 'jwt'], 200)]);

        Conveyor::getToken();

        Http::assertSent(function (Request $req) {
            return ! isset($req['channel']);
        });
    }

    public function test_get_token_returns_empty_default_when_response_lacks_auth(): void
    {
        Config::set('conveyor.query', 'k=v');
        Http::fake(['*' => Http::response([], 200)]);

        $this->assertSame('', Conveyor::getToken());
    }
}
