<?php

namespace App\Support;

use App\Models\PromotionClaim;
use Illuminate\Support\Str;

class VoucherCodeGenerator
{
    /**
     * Generate a unique 8-character uppercase voucher code.
     *
     * Loops until a code that does not exist in the database is found.
     * Collisions are extremely rare (26^8 ≈ 200 billion combinations)
     * but the uniqueness check guarantees correctness regardless.
     */
    public function generate(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (PromotionClaim::where('voucher_code', $code)->exists());

        return $code;
    }
}
