<?php

namespace Kanata\LaravelBroadcaster\Tests\Unit;

use Conveyor\SubProtocols\Conveyor\Persistence\Interfaces\UserAssocPersistenceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Kanata\LaravelBroadcaster\Conveyor;
use Kanata\LaravelBroadcaster\ConveyorDriver;
use Kanata\LaravelBroadcaster\Models\Token;
use Kanata\LaravelBroadcaster\Services\JwtToken;
use Kanata\LaravelBroadcaster\Tests\Stubs\User;
use Kanata\LaravelBroadcaster\Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ConveyorDriverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeDriver(?Conveyor $conveyor = null): ConveyorDriver
    {
        return new ConveyorDriver($conveyor ?? new Conveyor());
    }

    public function test_auth_throws_when_channel_name_empty(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $request = new Request();
        $request->channel_name = '';

        $this->makeDriver()->auth($request);
    }

    public function test_auth_throws_when_guarded_channel_has_no_authenticated_user(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $request = new Request();
        $request->channel_name = 'private-room';

        $this->makeDriver()->auth($request);
    }

    public function test_auth_passes_when_authenticated_user_can_access_guarded_channel(): void
    {
        $user = User::create(['name' => 'Owen']);
        $this->actingAs($user);

        $driver = $this->makeDriver();
        $driver->channel('room', fn ($u) => true);

        $request = new Request();
        $request->channel_name = 'private-room';
        $request->setUserResolver(fn () => $user);

        $result = $driver->auth($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('auth', $result);
    }

    public function test_valid_authentication_response_returns_jwt_token(): void
    {
        $user = User::create(['name' => 'Jay']);
        $this->actingAs($user);

        $response = $this->makeDriver()->validAuthenticationResponse(
            new Request(),
            null,
        );

        $this->assertArrayHasKey('auth', $response);
        $this->assertIsString($response['auth']);
        $this->assertSame(1, Token::query()->where('user_id', $user->getKey())->count());
    }

    public function test_validate_connection_consumes_token_when_user_present(): void
    {
        $user = User::create(['name' => 'Kim']);
        $token = JwtToken::create(
            name: 'conn-with-sufficiently-long-key-name',
            userId: (int) $user->getKey(),
            expire: null,
            useLimit: 1,
        );
        $token->update(['uses' => 2]);

        $this->makeDriver()->validateConnection($token->token);

        $this->assertSame(1, $token->fresh()->uses);
    }

    public function test_validate_connection_throws_when_token_missing(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->makeDriver()->validateConnection('does-not-exist');
    }

    public function test_validate_connection_throws_when_user_missing(): void
    {
        $orphan = Token::create([
            'name' => 'orphan',
            'user_id' => 9999,
            'token' => 'orphan-token',
            'uses' => 1,
        ]);

        $this->expectException(AuthorizationException::class);

        $this->makeDriver()->validateConnection($orphan->token);
    }

    public function test_broadcast_delegates_to_conveyor(): void
    {
        /** @var Conveyor&MockInterface $conveyor */
        $conveyor = Mockery::mock(Conveyor::class);
        $conveyor->shouldReceive('broadcast')
            ->once()
            ->with(['c'], 'evt', ['x' => 1]);

        $this->makeDriver($conveyor)->broadcast(['c'], 'evt', ['x' => 1]);
    }

    public function test_associate_user_returns_early_when_user_or_persistence_null(): void
    {
        /** @var UserAssocPersistenceInterface&MockInterface $persistence */
        $persistence = Mockery::mock(UserAssocPersistenceInterface::class);
        $persistence->shouldNotReceive('assoc');

        $this->makeDriver()->associateUser(1, null, $persistence);
        $this->makeDriver()->associateUser(1, User::create(['name' => 'L']), null);
        $this->addToAssertionCount(1);
    }

    public function test_associate_user_calls_assoc_with_fd_and_user_id(): void
    {
        $user = User::create(['name' => 'Mia']);

        /** @var UserAssocPersistenceInterface&MockInterface $persistence */
        $persistence = Mockery::mock(UserAssocPersistenceInterface::class);
        $persistence->shouldReceive('assoc')
            ->once()
            ->with(Mockery::on(fn ($v) => $v === 7), Mockery::on(fn ($v) => $v === (int) $user->getKey()));

        $this->makeDriver()->associateUser(7, $user, $persistence);
    }

    public function test_get_assoc_user_returns_null_when_persistence_null(): void
    {
        $this->assertNull($this->makeDriver()->getAssocUser(1, null));
    }

    public function test_get_assoc_user_resolves_user_via_configured_model(): void
    {
        $user = User::create(['name' => 'Nia']);

        /** @var UserAssocPersistenceInterface&MockInterface $persistence */
        $persistence = Mockery::mock(UserAssocPersistenceInterface::class);
        $persistence->shouldReceive('getAssoc')
            ->with(Mockery::on(fn ($v) => $v === 5))
            ->andReturn((int) $user->getKey());

        $resolved = $this->makeDriver()->getAssocUser(5, $persistence);

        $this->assertNotNull($resolved);
        $this->assertSame((int) $user->getKey(), (int) $resolved->getAuthIdentifier());
    }
}
