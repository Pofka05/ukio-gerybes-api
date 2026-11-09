<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtTokenService;
use Closure;
use DomainException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\UnexpectedValueException;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(private JwtTokenService $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return response()->json(['message' => 'Reikalinga autorizacija'], 401);
        }

        try {
            $claims = $this->tokens->decode($token);
        } catch (BeforeValidException|ExpiredException|SignatureInvalidException|UnexpectedValueException|DomainException|InvalidArgumentException $exception) {
            return response()->json(['message' => 'Negaliojantis arba pasibaigęs token'], 401);
        }

        if (($claims->type ?? null) !== 'access' || ! isset($claims->sub, $claims->jti, $claims->role) || $this->tokens->isRevoked($claims->jti)) {
            return response()->json(['message' => 'Netinkamas access token'], 401);
        }

        $user = User::find($claims->sub);

        if (! $user || $user->role !== $claims->role) {
            return response()->json(['message' => 'Naudotojas nebegalioja'], 401);
        }

        $request->attributes->set('jwt_claims', $claims);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
