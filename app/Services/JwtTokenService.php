<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

class JwtTokenService
{
    public const ACCESS_TTL = 900;

    public const REFRESH_TTL = 2592000;

    public function issueAccessToken(User $user): string
    {
        return $this->encode($user, 'access', self::ACCESS_TTL);
    }

    public function issueRefreshToken(User $user): string
    {
        return $this->encode($user, 'refresh', self::REFRESH_TTL);
    }

    public function decode(string $token): stdClass
    {
        return JWT::decode($token, new Key($this->key(), 'HS256'));
    }

    public function revoke(stdClass $claims): void
    {
        DB::table('jwt_token_blacklist')->insertOrIgnore([
            'jti' => $claims->jti,
            'expires_at' => now()->setTimestamp((int) $claims->exp),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function isRevoked(string $jti): bool
    {
        return DB::table('jwt_token_blacklist')->where('jti', $jti)->exists();
    }

    /**
     * @return array{sub: int, role: string, type: string, iat: int, exp: int, jti: string, iss: string}
     */
    public function claims(User $user, string $type, int $ttl): array
    {
        $now = now()->timestamp;

        return [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'role' => $user->role,
            'type' => $type,
            'iat' => $now,
            'exp' => $now + $ttl,
            'jti' => (string) Str::uuid(),
        ];
    }

    private function encode(User $user, string $type, int $ttl): string
    {
        return JWT::encode($this->claims($user, $type, $ttl), $this->key(), 'HS256');
    }

    private function key(): string
    {
        $key = (string) config('app.key');

        if (Str::startsWith($key, 'base64:')) {
            return base64_decode(Str::after($key, 'base64:'), true) ?: $key;
        }

        return $key;
    }
}
