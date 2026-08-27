<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function attempt(string $email, string $password, string $tokenName = 'spa'): array
    {
        /** @var User|null $user */
        $user = User::query()->with('role.permissions')->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный логин или пароль.'],
            ]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'email' => ['Учётная запись отключена.'],
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'first_login_at' => $user->first_login_at ?? now(),
        ])->save();

        $user->tokens()->where('name', $tokenName)->delete();
        $token = $user->createToken($tokenName);

        $this->audit->log($user, 'login', $user);

        return [
            'user' => $this->payload($user),
            'token' => $token->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
        $this->audit->log($user, 'logout', $user);
    }

    public function impersonate(User $actor, User $target): NewAccessToken
    {
        if (! $actor->isAdminLike()) {
            abort(403, 'Impersonation разрешён только администратору.');
        }

        $this->audit->log($actor, 'impersonate', $target, ['target_id' => $target->id]);

        return $target->createToken('impersonation');
    }

    public function payload(User $user): array
    {
        $user->loadMissing('role.permissions');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'active' => $user->active,
            'role' => $user->role?->code?->value,
            'rights' => $user->isAdminLike()
                ? ['*']
                : $user->role?->permissions->pluck('code')->values()->all() ?? [],
            'public_offer' => $user->publicOfferPhase(),
        ];
    }
}
