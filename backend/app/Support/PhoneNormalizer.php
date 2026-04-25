<?php

namespace App\Support;

class PhoneNormalizer
{
    public static function normalize(string $phone): string
    {
        // Strip whitespace and anything that isn't a digit or '+'
        $phone = preg_replace('/\s+/', '', $phone);
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Already in international E.164 format (e.g. +12025551234, +4915123456789)
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        // International dial-out prefix used in many countries: 0049... → +49...
        if (str_starts_with($phone, '00')) {
            return '+' . substr($phone, 2);
        }

        // Backward compatibility: Syrian local format (09xxxxxxxx) → +963xxxxxxxx
        if (str_starts_with($phone, '09')) {
            return '+963' . substr($phone, 1);
        }

        // Backward compatibility: bare Syrian country code (963...) → +963...
        if (str_starts_with($phone, '963')) {
            return '+' . $phone;
        }

        // Anything else: leave the digits as the caller provided them.
        // For non-Syrian numbers, callers should send international format
        // (e.g. "+12025551234"), which is preserved by the first branch above.
        return $phone;
    }
}