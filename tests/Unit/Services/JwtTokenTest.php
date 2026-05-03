<?php

namespace Kanata\LaravelBroadcaster\Tests\Unit\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Kanata\LaravelBroadcaster\Models\Token;
use Kanata\LaravelBroadcaster\Services\JwtToken;
use Kanata\LaravelBroadcaster\Tests\Stubs\User;
use Kanata\LaravelBroadcaster\Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class JwtTokenTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_persists_token_record_with_payload(): void
    {
        $user = User::create(['name' => 'Alice']);

        $token = JwtToken::create(
            name: 'session-with-sufficiently-long-secret-name-001',
            userId: (int) $user->getKey(),
            expire: 3600,
            useLimit: 5,
        );

        $this->assertInstanceOf(Token::class, $token);
        $this->assertSame('session-with-sufficiently-long-secret-name-001', $token->name);
        $this->assertSame((int) $user->getKey(), $token->user_id);
        $this->assertNotNull($token->expire_at);

        $decoded = (array) JWT::decode($token->token, new Key('session-with-sufficiently-long-secret-name-001', 'HS256'));
        $this->assertSame((int) $user->getKey(), $decoded['user_id']);
        $this->assertArrayHasKey('iat', $decoded);
        $this->assertArrayHasKey('exp', $decoded);
    }

    public function test_create_without_expiration_omits_exp_claim(): void
    {
        $user = User::create(['name' => 'Bob']);

        $token = JwtToken::create(
            name: 'session-no-exp-but-long-enough-key-length-002',
            userId: (int) $user->getKey(),
            expire: null,
        );

        $this->assertNull($token->expire_at);

        $decoded = (array) JWT::decode($token->token, new Key('session-no-exp-but-long-enough-key-length-002', 'HS256'));
        $this->assertArrayNotHasKey('exp', $decoded);
    }

    public function test_decode_jwt_token_returns_array(): void
    {
        $jwt = JWT::encode(['user_id' => 42, 'iat' => time()], 'sufficiently-long-secret-key-for-hmac-sha256', 'HS256');

        $decoded = JwtToken::decodeJwtToken($jwt, 'sufficiently-long-secret-key-for-hmac-sha256');

        $this->assertIsArray($decoded);
        $this->assertSame(42, $decoded['user_id']);
    }

    public function test_get_token_returns_null_when_no_authorization_header(): void
    {
        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')->with('Authorization')->andReturnFalse();

        $this->assertNull(JwtToken::getToken($request));
    }

    public function test_get_token_returns_null_when_header_missing_bearer_prefix(): void
    {
        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')->with('Authorization')->andReturnTrue();
        $request->shouldReceive('getHeader')->with('Authorization')->andReturn(['Basic abc']);

        $this->assertNull(JwtToken::getToken($request));
    }

    public function test_get_token_returns_null_when_only_scheme_present(): void
    {
        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')->with('Authorization')->andReturnTrue();
        $request->shouldReceive('getHeader')->with('Authorization')->andReturn(['Bearer']);

        $this->assertNull(JwtToken::getToken($request));
    }

    public function test_get_token_returns_null_when_token_not_found(): void
    {
        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')->with('Authorization')->andReturnTrue();
        $request->shouldReceive('getHeader')->with('Authorization')->andReturn(['Bearer nonexistent.token']);

        $this->assertNull(JwtToken::getToken($request));
    }

    public function test_get_token_loads_and_consumes_existing_token(): void
    {
        $user = User::create(['name' => 'Carol']);
        $token = JwtToken::create(
            name: 'bearer-test-with-long-enough-secret-name-007',
            userId: (int) $user->getKey(),
            expire: null,
            useLimit: 2,
        );
        $token->update(['uses' => 2]);

        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')->with('Authorization')->andReturnTrue();
        $request->shouldReceive('getHeader')->with('Authorization')->andReturn(['Bearer ' . $token->token]);

        $resolved = JwtToken::getToken($request);

        $this->assertInstanceOf(Token::class, $resolved);
        $this->assertSame($token->getKey(), $resolved->getKey());
        $this->assertSame(1, $resolved->fresh()->uses);
    }

    public function test_get_token_returns_null_when_database_throws(): void
    {
        config(['conveyor.database-driver' => 'nonexistent-driver']);

        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')->with('Authorization')->andReturnTrue();
        $request->shouldReceive('getHeader')->with('Authorization')->andReturn(['Bearer xyz']);

        $this->assertNull(JwtToken::getToken($request));
    }

    public function test_get_token_returns_null_when_first_header_value_is_not_string(): void
    {
        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')->with('Authorization')->andReturnTrue();
        $request->shouldReceive('getHeader')->with('Authorization')->andReturn([]);

        $this->assertNull(JwtToken::getToken($request));
    }
}
