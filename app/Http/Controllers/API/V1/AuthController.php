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
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            // Public registration only creates non-admin accounts.
            'is_admin' => false,
            'account_type' => 'student',
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->jsonSuccess([
            'user' => UserResource::make($user),
            'token' => $token,
        ], 201, 'User registered successfully');
    }

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

    public function showCurrentSession(Request $request): Response
    {
        return $this->handleSessionUser($request);
    }

    private function handleSessionUser(Request $request): Response
    {
        return response()->jsonSuccess(UserResource::make($request->user()));
    }

}
