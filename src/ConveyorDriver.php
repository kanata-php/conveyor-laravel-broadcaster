<?php

namespace Kanata\LaravelBroadcaster;

use Conveyor\SubProtocols\Conveyor\Persistence\Interfaces\UserAssocPersistenceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Broadcasting\Broadcasters\Broadcaster as BaseBroadcaster;
use Illuminate\Broadcasting\Broadcasters\UsePusherChannelConventions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as DefaultUser;
use Illuminate\Http\Request;
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

    /**
     * @param Request $request
     * @return mixed
     */
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

    /**
     * @param Request $request
     * @param mixed $result
     * @return array<string, string>
     */
    public function validAuthenticationResponse($request, $result)
    {
        /** @var Authenticatable $user */
        $user = auth()->user();

        return [
            'auth' => JwtToken::create(
                name: Uuid::uuid4()->toString(),
                userId: (int) $user->getAuthIdentifier(),
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
        $tokenModel = new Token();
        $tokenModel->setConnection(config('conveyor.database-driver'));
        /** @phpstan-ignore-next-line method.notFound (scopeByToken) */
        $tokenInstance = $tokenModel->byToken($token)->first();

        if (null === $tokenInstance || null === $tokenInstance->user) {
            throw new AuthorizationException('Unauthorized');
        }

        $tokenInstance->consume();
    }

    /**
     * @param array<int, mixed> $channels
     * @param string $event
     * @param array<string, mixed> $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $this->conveyor->broadcast($channels, $event, $payload);
    }

    /**
     * @param int $fd
     * @param (Authenticatable&Model)|null $user
     * @param ?UserAssocPersistenceInterface $assocPersistence
     * @return void
     */
    public function associateUser(
        int $fd,
        ?Authenticatable $user,
        ?UserAssocPersistenceInterface $assocPersistence,
    ): void {
        if (null === $user || null === $assocPersistence) {
            return;
        }

        $assocPersistence->assoc(
            fd: $fd,
            userId: (int) $user->getAuthIdentifier(),
        );
    }

    /**
     * @return (Authenticatable&Model)|null
     */
    public function getAssocUser(
        int $fd,
        ?UserAssocPersistenceInterface $assocPersistence,
    ): ?Authenticatable {
        if (null === $assocPersistence) {
            return null;
        }

        /** @var class-string<Authenticatable&Model> $model */
        $model = config('auth.providers.users.model', DefaultUser::class);

        return $model::find($assocPersistence->getAssoc(fd: $fd));
    }
}
