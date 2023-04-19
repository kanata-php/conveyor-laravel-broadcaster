<?php
 
namespace App\Providers;
 
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
 
class ConveyorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('conveyor', function (Application $app) {
            return new Conveyor();
        });
    }
}
