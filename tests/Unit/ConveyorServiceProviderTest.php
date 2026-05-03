<?php

namespace Kanata\LaravelBroadcaster\Tests\Unit;

use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Support\Facades\Config;
use Kanata\LaravelBroadcaster\Commands\ConveyorToken;
use Kanata\LaravelBroadcaster\ConveyorDriver;
use Kanata\LaravelBroadcaster\Tests\TestCase;

class ConveyorServiceProviderTest extends TestCase
{
    public function test_merges_default_config(): void
    {
        $this->assertNotNull(Config::get('conveyor.protocol'));
        $this->assertNotNull(Config::get('conveyor.uri'));
        $this->assertNotNull(Config::get('conveyor.port'));
        $this->assertSame('', Config::get('conveyor.query'));
    }

    public function test_registers_artisan_command(): void
    {
        $commands = array_keys($this->app[\Illuminate\Contracts\Console\Kernel::class]->all());
        $this->assertContains('conveyor:token', $commands);
        $this->assertSame(ConveyorToken::class, get_class($this->app->make(ConveyorToken::class)));
    }

    public function test_registers_conveyor_broadcast_driver(): void
    {
        Config::set('broadcasting.connections.conveyor', ['driver' => 'conveyor']);

        $driver = $this->app->make(BroadcastFactory::class)->driver('conveyor');

        $this->assertInstanceOf(ConveyorDriver::class, $driver);
    }
}
