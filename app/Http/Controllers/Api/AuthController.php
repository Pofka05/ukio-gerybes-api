<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtTokenService;
use DomainException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\UnexpectedValueException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class AuthController extends Controller
{
    public function __construct(private JwtTokenService $tokens) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', 'in:buyer,farmer'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'] ?? 'buyer',
        ]);

        return $this->tokenResponse($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Neteisingas el. paštas arba slaptažodis'], 401);
        }

        return $this->tokenResponse($user);
    }

    public function refresh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        try {
            $claims = $this->tokens->decode($validated['refresh_token']);
        } catch (BeforeValidException|ExpiredException|SignatureInvalidException|UnexpectedValueException|DomainException|InvalidArgumentException $exception) {
            return response()->json(['message' => 'Negaliojantis arba pasibaigęs refresh token'], 401);
        }

        if (($claims->type ?? null) !== 'refresh' || ! isset($claims->sub, $claims->jti) || $this->tokens->isRevoked($claims->jti)) {
            return response()->json(['message' => 'Netinkamas refresh token'], 401);
        }

        $user = User::find($claims->sub);

        if (! $user) {
            return response()->json(['message' => 'Naudotojas nerastas'], 401);
        }

        $this->tokens->revoke($claims);

        return $this->tokenResponse($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $claims = $request->attributes->get('jwt_claims');

        if ($claims !== null) {
            $this->tokens->revoke($claims);
        }

        if ($request->filled('refresh_token')) {
            try {
                $refreshClaims = $this->tokens->decode($request->string('refresh_token')->toString());
            } catch (BeforeValidException|ExpiredException|SignatureInvalidException|UnexpectedValueException|DomainException|InvalidArgumentException $exception) {
                return response()->json(['message' => 'Negaliojantis refresh token'], 422);
            }

            if (($refreshClaims->type ?? null) === 'refresh' && isset($refreshClaims->jti)) {
                $this->tokens->revoke($refreshClaims);
            }
        }

        return response()->json(['message' => 'Sėkmingai atsijungta']);
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $this->tokens->issueAccessToken($user),
            'expires_in' => JwtTokenService::ACCESS_TTL,
            'refresh_token' => $this->tokens->issueRefreshToken($user),
            'refresh_expires_in' => JwtTokenService::REFRESH_TTL,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], $status);
    }
}
