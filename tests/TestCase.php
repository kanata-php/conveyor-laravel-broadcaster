<?php

namespace Kanata\LaravelBroadcaster\Tests;

use Illuminate\Database\Schema\Blueprint;
use Kanata\LaravelBroadcaster\ConveyorServiceProvider;
use Kanata\LaravelBroadcaster\Tests\Stubs\User;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ConveyorServiceProvider::class];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('conveyor.database-driver', 'testing');
    }

    protected function createSchema(): void
    {
        $schema = $this->app['db']->connection()->getSchemaBuilder();

        $schema->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $schema->create('conveyor_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('user_id');
            $table->string('aud')->nullable();
            $table->text('token');
            $table->string('aud_protocol')->nullable();
            $table->integer('allowed_uses')->nullable();
            $table->integer('uses')->nullable();
            $table->dateTime('expire_at')->nullable();
            $table->timestamps();
        });
    }
}
