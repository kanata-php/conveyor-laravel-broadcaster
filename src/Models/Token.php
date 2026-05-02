<?php

namespace Kanata\LaravelBroadcaster\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as DefaultUser;

/**
 * @property string $token
 * @property int|null $uses
 * @property int|null $use_limit
 * @property int $user_id
 * @property string|null $expire_at
 *
 * @method static self create(array<string, mixed> $attributes = [])
 */
class Token extends Model
{
    public const TABLE_NAME = 'conveyor_tokens';

    protected $table = self::TABLE_NAME;

    /** @var array<string, mixed> */
    protected array $defaults = [];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'user_id',
        'expire_at',
        'aud',
        'token',
        'aud_protocol',
        'allowed_uses',
        'uses',
    ];

    // scopes

    /**
     * @param Builder<self> $query
     */
    public function scopeByToken(Builder $query, string $token): void
    {
        $query->where('token', $token);
    }

    /**
     * @return BelongsTo<Authenticatable&Model, $this>
     */
    public function user(): BelongsTo
    {
        /** @var class-string<Authenticatable&Model> $model */
        $model = config('auth.providers.users.model', DefaultUser::class);

        return $this->belongsTo($model);
    }

    public function consume(): self
    {
        if (null === $this->uses) {
            return $this;
        }

        if ($this->uses < 2) {
            $this->delete();
        } else {
            $this->decrement('uses');
        }

        return $this;
    }
}
