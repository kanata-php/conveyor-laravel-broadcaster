<?php

namespace Kanata\LaravelBroadcaster\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @var list<string> */
    protected $fillable = ['name'];

    /** @var string */
    protected $table = 'users';
}
