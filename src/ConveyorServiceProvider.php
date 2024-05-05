<?php

namespace Kanata\LaravelBroadcaster;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Kanata\LaravelBroadcaster\Commands\ConveyorToken;

class ConveyorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/conveyor.php', 'conveyor');
    }

    public function boot(): void
    {
        $this->loadDatabase();

        $this->publishes([
            __DIR__ . '/../config/conveyor.php' => config_path('conveyor.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ConveyorToken::class,
            ]);
        }

        Broadcast::extend('conveyor', function ($app) {
            return new ConveyorDriver(new Conveyor());
        });
    }

    private function loadDatabase(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Config::set('database.connections.conveyor', array_merge(
            Config::get('database.connections.sqlite'),
            [
                'database' => env('DB_DATABASE', database_path('conveyor.sqlite')),
            ]
        ));
    }
}
