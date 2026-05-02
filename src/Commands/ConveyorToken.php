<?php

namespace Kanata\LaravelBroadcaster\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as DefaultUser;
use Kanata\LaravelBroadcaster\Services\JwtToken;

class ConveyorToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conveyor:token {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a system-level token for the given user.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var class-string<Authenticatable&Model> $model */
        $model = config('auth.providers.users.model', DefaultUser::class);

        $user = $model::query()->whereKey($this->argument('user'))->first();
        if (null === $user) {
            $this->error('User not found!');
            return self::FAILURE;
        }

        $name = uniqid('', true) . '-system-token-' . $user->getKey();

        $token = JwtToken::create(
            name: $name,
            userId: (int) $user->getKey(),
            expire: null,
        );

        $this->line('Token generated successfully!');
        $this->info('Token Name: ' . $name);
        $this->info('Token: ' . $token->token);

        return self::SUCCESS;
    }
}
