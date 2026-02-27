<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    private const CANONICAL_REGISTRATIONS_PATH = '/api/v1/registrations';
    private const CANONICAL_SESSIONS_PATH = '/api/v1/sessions';
    private const CANONICAL_CURRENT_SESSION_PATH = '/api/v1/sessions/current';
    private const SUNSET_AT = '2026-06-30 23:59:59 UTC';

    /**
     * Deprecated endpoint. Use POST /api/v1/registrations.
     */
    public function register(Request $request): Response
    {
        $response = $this->handleRegister($request);

        return $this->withDeprecationHeaders($response, self::CANONICAL_REGISTRATIONS_PATH);
    }

    /**
     * Canonical endpoint for registration.
     */
    public function createRegistration(Request $request): Response
    {
        return $this->handleRegister($request);
    }

    private function handleRegister(Request $request): Response
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_admin' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'is_admin' => $request->is_admin ?? false,
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->jsonSuccess([
            'user' => UserResource::make($user),
            'token' => $token,
        ], 201, 'User registered successfully');
    }

    /**
     * Deprecated endpoint. Use POST /api/v1/sessions.
     */
    public function login(Request $request): Response
    {
        $response = $this->handleLogin($request);

        return $this->withDeprecationHeaders($response, self::CANONICAL_SESSIONS_PATH);
    }

    /**
     * Canonical endpoint for session creation (login).
     */
    public function createSession(Request $request): Response
    {
        return $this->handleLogin($request);
    }

    private function handleLogin(Request $request): Response
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated.',
            ]);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->jsonSuccess([
            'user' => UserResource::make($user),
            'token' => $token,
        ], 200, 'Login successful');
    }

    /**
     * Deprecated endpoint. Use DELETE /api/v1/sessions/current.
     */
    public function logout(Request $request): Response
    {
        $response = $this->handleLogout($request);

        return $this->withDeprecationHeaders($response, self::CANONICAL_CURRENT_SESSION_PATH);
    }

    /**
     * Canonical endpoint for session revoke (logout).
     */
    public function destroyCurrentSession(Request $request): Response
    {
        return $this->handleLogout($request);
    }

    private function handleLogout(Request $request): Response
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();

        return response()->jsonSuccess([], 200, 'Logged out successfully');
    }

    /**
     * Deprecated endpoint. Use GET /api/v1/sessions/current.
     */
    public function user(Request $request): Response
    {
        $response = $this->handleSessionUser($request);

        return $this->withDeprecationHeaders($response, self::CANONICAL_CURRENT_SESSION_PATH);
    }

    /**
     * Canonical endpoint for current authenticated session user.
     */
    public function showCurrentSession(Request $request): Response
    {
        return $this->handleSessionUser($request);
    }

    private function handleSessionUser(Request $request): Response
    {
        return response()->jsonSuccess(UserResource::make($request->user()));
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        // Delete current token
        /** @var \Laravel\Sanctum\PersonalAccessToken $currentToken */
        $currentToken = $request->user()->currentAccessToken();
        $currentToken->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->jsonSuccess([
            'token' => $token,
        ], 200, 'Token refreshed successfully');
    }

    private function withDeprecationHeaders(Response $response, string $replacementPath): Response
    {
        $sunset = gmdate('D, d M Y H:i:s \G\M\T', strtotime(self::SUNSET_AT));

        $response->headers->set('Deprecation', 'true');
        $response->headers->set('Sunset', $sunset);
        $response->headers->set('Link', sprintf('<%s>; rel="successor-version"', $replacementPath));

        return $response;
    }
}
