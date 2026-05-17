<?php

namespace Tests\Unit\Requests\Profile;

use App\Http\Requests\Profile\UpdateInterestsRequest;
use App\Models\Interest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateInterestsRequestTest extends TestCase
{
    use RefreshDatabase;

    private function rules(): array
    {
        return (new UpdateInterestsRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    public function test_interests_field_is_required(): void
    {
        $v = $this->validate([]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('interests', $v->errors()->toArray());
    }

    public function test_interests_must_be_an_array(): void
    {
        $v = $this->validate(['interests' => 'music']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('interests', $v->errors()->toArray());
    }

    public function test_interests_requires_at_least_one_item(): void
    {
        $v = $this->validate(['interests' => []]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('interests', $v->errors()->toArray());
    }

    public function test_interests_cannot_exceed_10_items(): void
    {
        // Create 11 real interests so the slug existence check passes
        $slugs = [];
        for ($i = 1; $i <= 11; $i++) {
            Interest::create(['name' => "Interest $i", 'slug' => "interest-$i"]);
            $slugs[] = "interest-$i";
        }

        $v = $this->validate(['interests' => $slugs]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('interests', $v->errors()->toArray());
    }

    public function test_each_slug_must_exist_in_interests_table(): void
    {
        $v = $this->validate(['interests' => ['does-not-exist']]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('interests.0', $v->errors()->toArray());
    }

    public function test_valid_existing_slugs_pass_validation(): void
    {
        Interest::create(['name' => 'Music', 'slug' => 'music']);
        Interest::create(['name' => 'Sports', 'slug' => 'sports']);

        $v = $this->validate(['interests' => ['music', 'sports']]);

        $this->assertFalse($v->fails());
    }

    public function test_mix_of_valid_and_invalid_slugs_fails(): void
    {
        Interest::create(['name' => 'Music', 'slug' => 'music']);

        $v = $this->validate(['interests' => ['music', 'nonexistent']]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('interests.1', $v->errors()->toArray());
    }
}
