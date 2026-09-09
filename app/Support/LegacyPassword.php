<?php

namespace App\Support;

class LegacyPassword
{
    public static function verify(string $plainText, ?string $storedPassword): bool
    {
        if ($storedPassword === null || $storedPassword === '') {
            return false;
        }

        if (password_verify($plainText, $storedPassword)) {
            return true;
        }

        if (mb_strlen($storedPassword) < 90) {
            return false;
        }

        $bcryptHash = mb_substr($storedPassword, 0, 30).mb_substr($storedPassword, 60, 30);

        return password_verify($plainText, $bcryptHash);
    }

    public static function make(string $plainText): string
    {
        $passwordHash = password_hash($plainText, PASSWORD_BCRYPT);
        $dateHash = password_hash(now()->format('d m Y H:i:s'), PASSWORD_BCRYPT);

        return mb_substr($passwordHash, 0, 30)
            .mb_substr($dateHash, 30, 30)
            .mb_substr($passwordHash, 30, 30)
            .mb_substr($dateHash, 8, 22);
    }
}
