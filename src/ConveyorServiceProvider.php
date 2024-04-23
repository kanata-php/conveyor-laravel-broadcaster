<?php

namespace Kanata\LaravelBroadcaster;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class ConveyorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/conveyor.php', 'conveyor');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/conveyor.php' => config_path('conveyor.php'),
        ]);

        Broadcast::extend('conveyor', function ($app) {
            return new ConveyorDriver(new Conveyor());
        });
    }
}
