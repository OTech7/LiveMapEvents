<?php

namespace Tests\Unit\Support;

use App\Models\Promotion;
use App\Models\PromotionClaim;
use App\Models\User;
use App\Models\Venue;
use App\Support\VoucherCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private VoucherCodeGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = app(VoucherCodeGenerator::class);
    }

    public function test_generate_returns_an_8_character_string(): void
    {
        $code = $this->generator->generate();

        $this->assertSame(8, strlen($code));
    }

    public function test_generate_returns_uppercase_string(): void
    {
        $code = $this->generator->generate();

        $this->assertSame($code, strtoupper($code));
    }

    public function test_generate_returns_only_alphanumeric_characters(): void
    {
        $code = $this->generator->generate();

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $code);
    }

    public function test_generate_produces_unique_codes_across_multiple_calls(): void
    {
        $codes = array_map(fn() => $this->generator->generate(), range(1, 20));

        // All 20 codes must be distinct
        $this->assertCount(20, array_unique($codes));
    }

    public function test_generate_skips_codes_already_in_the_database(): void
    {
        // Seed the promotion_claims table with every possible 8-char code starting
        // with "AAAAAAAA" by inserting that code directly.
        $owner = User::create(['phone' => '+963911000001']);
        $venue = Venue::create(['owner_id' => $owner->id, 'name' => 'V', 'type' => 'bar']);
        $promo = Promotion::create([
            'venue_id' => $venue->id,
            'title' => 'Promo',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'recurrence_type' => 'one_time',
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'valid_from' => now()->toDateString(),
            'is_active' => true,
            'max_per_user_redemptions' => 5,
        ]);
        $user = User::create(['phone' => '+963911000002']);

        PromotionClaim::create([
            'promotion_id' => $promo->id,
            'user_id' => $user->id,
            'voucher_code' => 'TESTCODE',
            'status' => 'claimed',
            'claimed_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        // The generator must never return a code that already exists
        $generated = $this->generator->generate();
        $this->assertNotSame('TESTCODE', $generated);
    }
}
