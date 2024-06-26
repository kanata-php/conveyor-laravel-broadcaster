<?php

namespace Kanata\LaravelBroadcaster;

use App\Models\User;
use Conveyor\SubProtocols\Conveyor\Persistence\Interfaces\UserAssocPersistenceInterface;
use Illuminate\Auth\Access\AuthorizationException;
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
     * @throws AuthorizationException
     */
    public function validateConnection(string $token): void
    {
        $tokenModel = new Token;
        $tokenModel->setConnection(config('conveyor.database-driver'));
        $tokenInstance = $tokenModel->byToken($token)->first();

        if (null === $tokenInstance || null === $tokenInstance->user) {
            throw new AuthorizationException('Unauthorized');
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

    /**
     * @param int $fd
     * @param User|null $user
     * @param ?UserAssocPersistenceInterface $assocPersistence
     * @return void
     */
    public function associateUser(
        int $fd,
        ?User $user,
        ?UserAssocPersistenceInterface $assocPersistence,
    ): void {
        if (null === $user || null === $assocPersistence) {
            return;
        }

        $assocPersistence->assoc(
            fd: $fd,
            userId: $user->id,
        );
    }

    public function getAssocUser(
        int $fd,
        ?UserAssocPersistenceInterface $assocPersistence,
    ): ?User {
        if (null === $assocPersistence) {
            return null;
        }

        return User::find($assocPersistence->getAssoc(fd: $fd));
    }
}
