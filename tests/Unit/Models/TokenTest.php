<?php

namespace Kanata\LaravelBroadcaster\Tests\Unit\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kanata\LaravelBroadcaster\Models\Token;
use Kanata\LaravelBroadcaster\Tests\Stubs\User;
use Kanata\LaravelBroadcaster\Tests\TestCase;

class TokenTest extends TestCase
{
    public function test_table_constant_matches(): void
    {
        $this->assertSame('conveyor_tokens', Token::TABLE_NAME);
        $this->assertSame('conveyor_tokens', (new Token())->getTable());
    }

    public function test_user_returns_belongs_to_configured_model(): void
    {
        $token = new Token();

        $relation = $token->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    public function test_scope_by_token_filters_by_token_string(): void
    {
        $user = User::create(['name' => 'Dan']);
        $a = Token::create(['name' => 'a', 'user_id' => $user->getKey(), 'token' => 'aaa']);
        Token::create(['name' => 'b', 'user_id' => $user->getKey(), 'token' => 'bbb']);

        /** @phpstan-ignore-next-line method.notFound (scope) */
        $found = Token::byToken('aaa')->get();

        $this->assertCount(1, $found);
        $this->assertSame($a->getKey(), $found->first()->getKey());
    }

    public function test_consume_returns_self_when_uses_is_null(): void
    {
        $user = User::create(['name' => 'Eve']);
        $token = Token::create([
            'name' => 't',
            'user_id' => $user->getKey(),
            'token' => 'tok',
            'uses' => null,
        ]);

        $result = $token->consume();

        $this->assertSame($token, $result);
        $this->assertNotNull(Token::find($token->getKey()));
    }

    public function test_consume_decrements_uses_when_above_one(): void
    {
        $user = User::create(['name' => 'Frank']);
        $token = Token::create([
            'name' => 't',
            'user_id' => $user->getKey(),
            'token' => 'tok',
            'uses' => 3,
        ]);

        $token->consume();

        $this->assertSame(2, $token->fresh()->uses);
    }

    public function test_consume_deletes_token_when_uses_is_one(): void
    {
        $user = User::create(['name' => 'Grace']);
        $token = Token::create([
            'name' => 't',
            'user_id' => $user->getKey(),
            'token' => 'tok',
            'uses' => 1,
        ]);

        $token->consume();

        $this->assertNull(Token::find($token->getKey()));
    }
}
