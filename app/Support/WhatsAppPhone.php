<?php

namespace App\Support;

final class WhatsAppPhone
{
    public static function normalize(string $value): ?string
    {
        if (strlen($value) > 32 || ! preg_match('/^[+0-9\s()-]+$/', $value)) {
            return null;
        }

        $number = preg_replace('/[\s()-]+/', '', $value);
        if (str_starts_with($number, '+62')) {
            $number = substr($number, 1);
        } elseif (str_starts_with($number, '08')) {
            $number = '62'.substr($number, 1);
        }

        return self::isValid($number) ? $number : null;
    }

    public static function isValid(?string $number): bool
    {
        return preg_match('/\A628[0-9]{8,11}\z/', $number ?? '') === 1;
    }
}
