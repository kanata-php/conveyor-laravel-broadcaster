<?php

namespace Kanata\LaravelBroadcaster\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Token extends Model
{
    const TABLE_NAME = 'conveyor_tokens';

    protected $connection = 'conveyor';

    protected $table = self::TABLE_NAME;

    protected array $defaults = [];

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

    public function scopeByToken($query, string $token)
    {
        $query->where('token', $token);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
