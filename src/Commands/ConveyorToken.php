<?php

namespace Kanata\LaravelBroadcaster\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Kanata\LaravelBroadcaster\Services\JwtToken;
use Kanata\LaravelBroadcaster\Models\Token;

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
    public function handle()
    {
        $user = User::find($this->argument('user'));
        if (null === $user) {
            $this->error('User not found!');
            return;
        }

        $name = uniqid() . '-system-token-' . $user->getKey();

        /** @var Token $token */
        $token = JwtToken::create(
            name: $name,
            userId: $user->getKey(),
            expire: null,
        );

        $this->line('Token generated successfully!');
        $this->info('Token Name: ' . $name);
        $this->info('Token: ' . $token->token);
    }
}
