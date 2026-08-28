<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

class DealerCalcSsoService
{
    public function createRedirectUrl(User $user): string
    {
        $this->assertUserCanSso($user);

        $callbackUrl = (string) config('services.dealer_calc.sso_callback_url');
        $secret = (string) config('services.dealer_calc.sso_secret');

        if ($callbackUrl === '' || $secret === '') {
            throw new RuntimeException('Dealer-calc SSO is not configured.');
        }

        $token = $this->createToken($user, $secret);
        $separator = str_contains($callbackUrl, '?') ? '&' : '?';

        return $callbackUrl.$separator.'token='.urlencode($token);
    }

    private function assertUserCanSso(User $user): void
    {
        if (! $user->active) {
            throw new RuntimeException('User is not active.');
        }

        if ($user->isAdminLike() || $user->roleCode()?->value === 'dealer') {
            return;
        }

        throw new RuntimeException('SSO is available for dealers only.');
    }

    private function createToken(User $user, string $secret): string
    {
        $ttl = (int) config('services.dealer_calc.sso_token_ttl', 60);
        $issuedAt = time();
        $payload = [
            'iss' => 'autobroker',
            'sub' => (string) $user->id,
            'email' => (string) $user->email,
            'name' => (string) ($user->name ?: $user->email),
            'iat' => $issuedAt,
            'exp' => $issuedAt + $ttl,
            'nonce' => bin2hex(random_bytes(16)),
        ];
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$body, $secret, true));

        return $header.'.'.$body.'.'.$signature;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
