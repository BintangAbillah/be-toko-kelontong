<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use InvalidArgumentException;

class JwtService
{
    public function issue(User $user): string
    {
        $now = Carbon::now();
        $ttl = (int) Config::get('jwt.ttl', 1440);

        return $this->encode([
            'iss' => Config::get('app.url'),
            'iat' => $now->timestamp,
            'nbf' => $now->timestamp,
            'exp' => $now->copy()->addMinutes($ttl)->timestamp,
            'sub' => (string) $user->getKey(),
            'jti' => (string) Str::uuid(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Invalid token format.');
        }

        [$encodedHeader, $encodedPayload, $signature] = $parts;
        $expected = $this->sign($encodedHeader.'.'.$encodedPayload);

        if (! hash_equals($expected, $signature)) {
            throw new InvalidArgumentException('Invalid token signature.');
        }

        $header = $this->jsonDecode($encodedHeader);
        $payload = $this->jsonDecode($encodedPayload);

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new InvalidArgumentException('Unsupported token algorithm.');
        }

        $now = Carbon::now()->timestamp;

        if (($payload['nbf'] ?? 0) > $now || ($payload['exp'] ?? 0) <= $now) {
            throw new InvalidArgumentException('Token is not active.');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ], JSON_THROW_ON_ERROR));

        $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));

        return $header.'.'.$body.'.'.$this->sign($header.'.'.$body);
    }

    private function sign(string $value): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $value, $this->secret(), true));
    }

    private function secret(): string
    {
        $key = (string) Config::get('app.key');

        if (str_starts_with($key, 'base64:')) {
            return base64_decode(substr($key, 7), true) ?: $key;
        }

        return $key;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonDecode(string $value): array
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid token encoding.');
        }

        return json_decode($decoded, true, flags: JSON_THROW_ON_ERROR);
    }
}
