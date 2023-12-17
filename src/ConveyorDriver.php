<?php

namespace Kanata\LaravelBroadcaster;

use App\Models\User;
use Conveyor\Models\WsAssociation;
use Exception;
use Illuminate\Broadcasting\Broadcasters\Broadcaster as BaseBroadcaster;
use Illuminate\Broadcasting\Broadcasters\UsePusherChannelConventions;
use Kanata\LaravelBroadcaster\Models\Token;
use Kanata\LaravelBroadcaster\Services\JwtToken;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ConveyorDriver extends BaseBroadcaster
{
    use UsePusherChannelConventions;

    public function __construct(
        protected Conveyor $conveyor
    ) {
    }

    public function auth($request)
    {
        $channelName = $this->normalizeChannelName(
            channel: $request->channel_name,
        );

        if (
            empty($request->channel_name)
            || (
                $this->isGuardedChannel($request->channel_name)
                && ! $this->retrieveUser($request, $channelName)
            )
        ) {
            throw new AccessDeniedHttpException();
        }

        return parent::verifyUserCanAccessChannel(
            $request,
            $channelName
        );
    }

    public function validAuthenticationResponse($request, $result)
    {
        return [
            'auth' => JwtToken::create(
                name: Uuid::uuid4()->toString(),
                userId: auth()->user()->id,
                expire: null,
                useLimit: 1,
            )->token,
        ];
    }

    /**
     * @param string $token
     * @return void
     * @throws Exception
     */
    public function validateConnection(string $token): void
    {
        $tokenInstance = Token::byToken($token)->first();

        if (null === $tokenInstance || null === $tokenInstance->user) {
            throw new Exception('Unauthorized');
        }

        $tokenInstance->consume();
    }

    /**
     * @param array $channels
     * @param string $event
     * @param array $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $this->conveyor->broadcast($channels, $event, $payload);
    }

    public function associateUser(int $fd, ?User $user): void
    {
        if (null === $user) {
            return;
        }

        WsAssociation::create([
            'fd' => $fd,
            'user_id' => $user->id,
        ]);
    }
}
