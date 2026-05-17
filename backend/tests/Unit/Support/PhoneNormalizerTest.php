<?php

namespace Tests\Unit\Support;

use App\Support\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    // ─── E.164 / international format ────────────────────────────────────────

    public function test_international_format_starting_with_plus_is_preserved(): void
    {
        $this->assertSame('+12025551234', PhoneNormalizer::normalize('+12025551234'));
    }

    public function test_international_format_with_country_code_preserved(): void
    {
        $this->assertSame('+4915123456789', PhoneNormalizer::normalize('+4915123456789'));
    }

    // ─── 00 dial-out prefix ───────────────────────────────────────────────────

    public function test_00_prefix_is_converted_to_plus(): void
    {
        $this->assertSame('+4915123456789', PhoneNormalizer::normalize('004915123456789'));
    }

    public function test_00_prefix_with_us_number_converted(): void
    {
        $this->assertSame('+12025551234', PhoneNormalizer::normalize('0012025551234'));
    }

    // ─── Syrian local format ─────────────────────────────────────────────────

    public function test_syrian_09_local_format_converted_to_international(): void
    {
        $this->assertSame('+9639' . '12345678', PhoneNormalizer::normalize('0912345678'));
    }

    public function test_syrian_09_format_another_number(): void
    {
        $this->assertSame('+9639' . '33333333', PhoneNormalizer::normalize('0933333333'));
    }

    // ─── Syrian bare country code ─────────────────────────────────────────────

    public function test_syrian_963_prefix_gets_plus_prepended(): void
    {
        $this->assertSame('+963912345678', PhoneNormalizer::normalize('963912345678'));
    }

    // ─── Whitespace & special characters ─────────────────────────────────────

    public function test_whitespace_is_stripped(): void
    {
        $this->assertSame('+12025551234', PhoneNormalizer::normalize('+1 202 555 1234'));
    }

    public function test_dashes_and_parens_are_stripped(): void
    {
        $this->assertSame('+12025551234', PhoneNormalizer::normalize('+1-(202)-555-1234'));
    }

    public function test_mixed_whitespace_and_special_chars_stripped(): void
    {
        $this->assertSame('+4915123456789', PhoneNormalizer::normalize('+49 151 234 567 89'));
    }

    // ─── Unknown / fallback ──────────────────────────────────────────────────

    public function test_unknown_format_is_returned_as_digit_string(): void
    {
        // Not +, not 00, not 09, not 963 — returned as-is (digits only)
        $this->assertSame('5551234', PhoneNormalizer::normalize('555-1234'));
    }
}
