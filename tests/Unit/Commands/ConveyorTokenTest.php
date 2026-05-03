<?php

namespace Kanata\LaravelBroadcaster\Tests\Unit\Commands;

use Kanata\LaravelBroadcaster\Models\Token;
use Kanata\LaravelBroadcaster\Tests\Stubs\User;
use Kanata\LaravelBroadcaster\Tests\TestCase;

class ConveyorTokenTest extends TestCase
{
    public function test_command_fails_when_user_not_found(): void
    {
        $this->artisan('conveyor:token', ['user' => 999])
            ->expectsOutput('User not found!')
            ->assertExitCode(1);
    }

    public function test_command_creates_token_for_user(): void
    {
        $user = User::create(['name' => 'Ivy']);

        $this->artisan('conveyor:token', ['user' => $user->getKey()])
            ->expectsOutputToContain('Token generated successfully!')
            ->assertExitCode(0);

        $this->assertSame(1, Token::query()->where('user_id', $user->getKey())->count());
    }
}
