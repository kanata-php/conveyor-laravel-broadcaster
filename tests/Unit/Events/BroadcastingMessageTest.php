<?php

namespace Kanata\LaravelBroadcaster\Tests\Unit\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Kanata\LaravelBroadcaster\Events\BroadcastingMessage;
use Kanata\LaravelBroadcaster\Tests\Stubs\User;
use Kanata\LaravelBroadcaster\Tests\TestCase;

class BroadcastingMessageTest extends TestCase
{
    public function test_broadcasts_on_private_broadcast_action_channel(): void
    {
        $user = User::create(['name' => 'Hank']);

        $event = new BroadcastingMessage($user);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-broadcast-action', $channels[0]->name);
        $this->assertSame($user, $event->user);
    }
}
