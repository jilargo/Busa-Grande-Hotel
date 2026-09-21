<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF protection.
 *
 * A random token is bound to the session. Every unsafe (POST) request must
 * carry it; requiring it stops cross-site request forgery because an attacker
 * can never read the victim's session token.
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        $session = Session::getInstance();

        if (!$session->has(self::KEY)) {
            $session->put(self::KEY, bin2hex(random_bytes(32)));
        }

        return (string) $session->get(self::KEY);
    }

    /** Compares tokens in constant time to avoid timing attacks. */
    public static function validate(string $token): bool
    {
        $expected = Session::getInstance()->get(self::KEY, '');

        if ($expected === '' || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    /** Returns a ready-to-print hidden input for use inside <form> tags. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }
}