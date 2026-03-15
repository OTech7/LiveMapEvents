<?php

namespace App\Support;

class PhoneNormalizer
{
    public static function normalize(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '09')) {
            $phone = '+963' . substr($phone, 1);
        }

        if (str_starts_with($phone, '963')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }
}