<?php

namespace Kanata\LaravelBroadcaster;

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Kanata\LaravelBroadcaster\Models\Token;
use Kanata\LaravelBroadcaster\Services\JwtToken;

class ConveyorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/conveyor.php', 'conveyor');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/conveyor.php' => config_path('conveyor.php'),
        ]);

        Broadcast::extend('conveyor', function ($app) {
            return new ConveyorDriver(new Conveyor());
        });
    }
}
