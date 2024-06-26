<?php

namespace Kanata\LaravelBroadcaster\Services;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Kanata\LaravelBroadcaster\Models\Token;
use Psr\Http\Message\ServerRequestInterface as Request;

class JwtToken
{
    public const HS256_ALGORITHM = 'HS256';

    /**
     * @param string $token
     * @param string $name
     * @return array<array-key, string>
     */
    public static function decodeJwtToken(string $token, string $name): array
    {
        $decoded = JWT::decode($token, new Key($name, self::HS256_ALGORITHM));

        return (array) $decoded;
    }

    public static function getToken(Request $request): ?Token
    {
        global $app;

        if (!$request->hasHeader('Authorization')) {
            return null;
        }

        $authorization = $request->getHeader('Authorization');
        $authorization = current($authorization);
        $authorization = explode(' ', $authorization);

        if (isset($authorization[0]) || $authorization[0] !== 'Bearer') {
            return null;
        }

        $token = $authorization[1] ?? null;

        try {
            $tokenModel = new Token;
            $tokenModel->setConnection(config('conveyor.database-driver'));
            $tokenRecord = $tokenModel->where('token', $token)->first()->consume();
        } catch (Exception $e) {
            $app->getContainer()->get('logger')->error('Invalid token: ' . $e->getMessage());
            return null;
        }

        return $tokenRecord;
    }

    /**
     * @param string $name Token's name.
     * @param int $userId User to attach to token.
     * @param int $expire Seconds to expire.
     * @param ?int $useLimit Uses limit number for token. Null for no limit.
     * @return Token
     */
    public static function create(string $name, int $userId, ?int $expire, ?int $useLimit = null): Token
    {
        if (null !== $expire) {
            $expire = Carbon::now()->addSeconds($expire);
        }

        $payload = [
            "iat" => Carbon::now()->timestamp,
            "user_id" => $userId,
        ];

        $tokenData = [
            'name' => $name,
            'user_id' => $userId,
            'expire_at' => null,
        ];

        if ($expire instanceof Carbon) {
            $payload["exp"] = $expire->timestamp;
            $tokenData['expire_at'] = $expire->format('Y-m-d H:i:s');
        }

        $tokenData['token'] = JWT::encode($payload, $name, JwtToken::HS256_ALGORITHM);

        if (null !== $useLimit) {
            $tokenData['use_limit'] = $useLimit;
        }

        $tokenModel = new Token;
        $tokenModel->setConnection(config('conveyor.database-driver'));
        return $tokenModel->create($tokenData);
    }
}
