<?php

namespace Tests\Unit\Requests\Profile;

use App\Http\Requests\Profile\UpdateDiscoverySettingsRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateDiscoverySettingsRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new UpdateDiscoverySettingsRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    public function test_radius_is_required(): void
    {
        $v = $this->validate([]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('radius', $v->errors()->toArray());
    }

    public function test_radius_must_be_an_integer(): void
    {
        $v = $this->validate(['radius' => 'abc']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('radius', $v->errors()->toArray());
    }

    public function test_radius_rejects_value_below_100(): void
    {
        $v = $this->validate(['radius' => 99]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('radius', $v->errors()->toArray());
    }

    public function test_radius_rejects_value_above_5000(): void
    {
        $v = $this->validate(['radius' => 5001]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('radius', $v->errors()->toArray());
    }

    public function test_radius_accepts_boundary_minimum_of_100(): void
    {
        $v = $this->validate(['radius' => 100]);

        $this->assertFalse($v->fails());
    }

    public function test_radius_accepts_boundary_maximum_of_5000(): void
    {
        $v = $this->validate(['radius' => 5000]);

        $this->assertFalse($v->fails());
    }

    public function test_notifications_is_optional(): void
    {
        $v = $this->validate(['radius' => 500]);

        $this->assertFalse($v->fails());
    }

    public function test_notifications_must_be_boolean_when_provided(): void
    {
        $v = $this->validate(['radius' => 500, 'notifications' => 'yes']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('notifications', $v->errors()->toArray());
    }

    public function test_notifications_accepts_true(): void
    {
        $v = $this->validate(['radius' => 500, 'notifications' => true]);

        $this->assertFalse($v->fails());
    }

    public function test_notifications_accepts_false(): void
    {
        $v = $this->validate(['radius' => 500, 'notifications' => false]);

        $this->assertFalse($v->fails());
    }
}
