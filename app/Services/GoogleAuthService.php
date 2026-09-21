<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\HttpException;
use App\Core\Logger;

/**
 * Google "Sign in with Google" via the OAuth 2.0 / OpenID Connect flow.
 *
 * Request flow:
 *   Browser ──▶ GET /auth/google ──▶ Google login ──▶ callback?code=...
 *     ──▶ POST https://oauth2.googleapis.com/token  (code + secret)
 *     ──▶ GET  https://oauth2.googleapis.com/tokeninfo?access_token=...
 *          (Google itself verifies signature/expiry and returns the profile)
 *     ──▶ aud claim must equal our GOOGLE_CLIENT_ID  ← the link back to us
 *     ──▶ email_verified must be true
 *
 * We only store Google's user identifier (sub), name and email locally.
 * The access token is never persisted.
 */
final class GoogleAuthService
{
    public function configured(): bool
    {
        return config('google.client_id') !== ''
            && config('google.client_secret') !== ''
            && config('google.redirect_uri') !== '';
    }

    /** Builds the Google consent URL. */
    public function authorizationUrl(string $state): string
    {
        $params = [
            'client_id'     => config('google.client_id'),
            'redirect_uri'  => config('google.redirect_uri'),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'prompt'        => 'select_account',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Exchanges the authorization code and returns a verified Google profile.
     *
     * @throws HttpException when Google rejects anything
     */
    public function getUserProfile(string $code): array
    {
        $tokenData = $this->exchangeCode($code);

        if (empty($tokenData['access_token'])) {
            throw new HttpException(401, 'Google did not return an access token.');
        }

        $info = $this->introspect($tokenData['access_token']);

        $clientId = (string) config('google.client_id');

        // The token must have been issued to THIS application.
        if ($info['aud'] ?? '' !== $clientId) {
            Logger::warning('Google token aud mismatch', ['aud' => $info['aud'] ?? null]);
            throw new HttpException(401, 'Google sign-in could not be verified.');
        }

        if (!(bool) ($info['email_verified'] ?? false) || empty($info['email'])) {
            throw new HttpException(401, 'Please verify your Google email address before signing in.');
        }

        return [
            'google_id' => (string) ($info['sub'] ?? ''),
            'name'      => trim((string) ($info['name'] ?? $info['email'])),
            'email'     => strtolower((string) $info['email']),
        ];
    }

    private function exchangeCode(string $code): array
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => http_build_query([
                'code'          => $code,
                'client_id'     => config('google.client_id'),
                'client_secret' => config('google.client_secret'),
                'redirect_uri'  => config('google.redirect_uri'),
                'grant_type'    => 'authorization_code',
            ]),
        ]);

        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($body === false || (int) $info['http_code'] !== 200) {
            Logger::error('Google token exchange failed', ['http' => (int) ($info['http_code'] ?? 0)]);
            throw new HttpException(502, 'Google sign-in is temporarily unavailable.');
        }

        return json_decode((string) $body, true) ?: [];
    }

    /** Asks Google to validate the access token and return its claims. */
    private function introspect(string $accessToken): array
    {
        $ch = curl_init('https://oauth2.googleapis.com/tokeninfo');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => http_build_query(['access_token' => $accessToken]),
        ]);

        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($body === false || (int) $info['http_code'] !== 200) {
            Logger::error('Google tokeninfo failed', ['http' => (int) ($info['http_code'] ?? 0)]);
            throw new HttpException(502, 'Google sign-in could not be verified.');
        }

        return json_decode((string) $body, true) ?: [];
    }
}