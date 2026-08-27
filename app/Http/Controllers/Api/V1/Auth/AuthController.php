<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->attempt(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->string('device', 'spa')->toString(),
        );

        $cookie = cookie(
            'access_token',
            $result['token'],
            60 * 24,
            '/',
            null,
            $request->secure(),
            true,
            false,
            'lax',
        );

        return response()->json($result)->withCookie($cookie);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $this->auth->logout($request->user());
        }

        return response()->json(['ok' => true])->withoutCookie('access_token');
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->auth->payload($request->user()));
    }

    public function impersonate(Request $request, User $user): JsonResponse
    {
        $this->authorize('impersonate', User::class);

        $token = $this->auth->impersonate($request->user(), $user);

        return response()->json([
            'user' => $this->auth->payload($user->load('role.permissions')),
            'token' => $token->plainTextToken,
        ]);
    }

    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return response()->json(['ok' => true]);
    }

    public function acceptOffer(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill([
            'public_offer_status' => 'accepted',
            'public_offer_accepted_at' => now(),
        ])->save();

        return response()->json($this->auth->payload($user->fresh('role.permissions')));
    }
}
