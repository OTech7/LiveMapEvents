<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use App\Services\GoogleAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    // ─── loginWithPhone() ─────────────────────────────────────────────────────

    public function test_login_with_phone_creates_a_new_user_when_phone_not_registered(): void
    {
        $service = app(AuthService::class);

        $result = $service->loginWithPhone('+963911000001');

        $this->assertDatabaseHas('users', ['phone' => '+963911000001']);
        $this->assertNotEmpty($result['token']);
    }

    public function test_login_with_phone_returns_existing_user_without_creating_duplicate(): void
    {
        User::create(['phone' => '+963911000002']);

        $service = app(AuthService::class);
        $service->loginWithPhone('+963911000002');

        $this->assertSame(1, User::where('phone', '+963911000002')->count());
    }

    public function test_login_with_phone_result_includes_all_required_keys(): void
    {
        $service = app(AuthService::class);

        $result = $service->loginWithPhone('+963911000003');

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('profile_complete', $result);
        $this->assertArrayHasKey('interests_complete', $result);
        $this->assertArrayHasKey('discovery_settings_complete', $result);
        $this->assertArrayHasKey('user', $result);
    }

    public function test_login_with_phone_sets_profile_complete_false_for_new_user(): void
    {
        $service = app(AuthService::class);

        $result = $service->loginWithPhone('+963911000004');

        $this->assertFalse($result['profile_complete']);
    }

    // ─── loginWithGoogle() ────────────────────────────────────────────────────

    public function test_login_with_google_creates_user_when_google_id_not_registered(): void
    {
        // Mock GoogleAuthService to return a predictable payload
        $mock = $this->createMock(GoogleAuthService::class);
        $mock->method('verify')->willReturn([
            'sub' => 'google-uid-001',
            'given_name' => 'Omar',
            'family_name' => 'Allouni',
            'picture' => null,
            'dob' => null,
        ]);

        $this->app->instance(GoogleAuthService::class, $mock);

        $service = app(AuthService::class);
        $result = $service->loginWithGoogle('fake-id-token');

        $this->assertDatabaseHas('users', ['google_id' => 'google-uid-001']);
        $this->assertNotEmpty($result['token']);
    }

    public function test_login_with_google_returns_existing_user_matched_by_google_id(): void
    {
        User::create([
            'google_id' => 'google-uid-002',
            'first_name' => 'Existing',
            'last_name' => 'User',
        ]);

        $mock = $this->createMock(GoogleAuthService::class);
        $mock->method('verify')->willReturn([
            'sub' => 'google-uid-002',
            'given_name' => 'Different',
            'family_name' => 'Name',
            'picture' => null,
            'dob' => null,
        ]);

        $this->app->instance(GoogleAuthService::class, $mock);

        $service = app(AuthService::class);
        $service->loginWithGoogle('fake-id-token');

        // Should still be only 1 record for this google_id
        $this->assertSame(1, User::where('google_id', 'google-uid-002')->count());
    }

    public function test_login_with_google_result_includes_all_required_keys(): void
    {
        $mock = $this->createMock(GoogleAuthService::class);
        $mock->method('verify')->willReturn([
            'sub' => 'google-uid-003',
            'given_name' => 'Test',
            'family_name' => 'User',
            'picture' => null,
            'dob' => null,
        ]);

        $this->app->instance(GoogleAuthService::class, $mock);

        $service = app(AuthService::class);
        $result = $service->loginWithGoogle('fake-id-token');

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('profile_complete', $result);
        $this->assertArrayHasKey('interests_complete', $result);
        $this->assertArrayHasKey('discovery_settings_complete', $result);
        $this->assertArrayHasKey('user', $result);
    }

    public function test_login_with_google_populates_name_from_google_payload(): void
    {
        $mock = $this->createMock(GoogleAuthService::class);
        $mock->method('verify')->willReturn([
            'sub' => 'google-uid-004',
            'given_name' => 'Jane',
            'family_name' => 'Doe',
            'picture' => null,
            'dob' => null,
        ]);

        $this->app->instance(GoogleAuthService::class, $mock);

        $service = app(AuthService::class);
        $service->loginWithGoogle('fake-id-token');

        $this->assertDatabaseHas('users', [
            'google_id' => 'google-uid-004',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
    }
}
