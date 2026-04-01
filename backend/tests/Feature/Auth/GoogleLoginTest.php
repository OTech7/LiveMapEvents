<?php

namespace Tests\Feature\Auth;

use App\Services\GoogleAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_google()
    {
        $this->mock(GoogleAuthService::class, function ($mock) {
            $mock->shouldReceive('verify')
                ->andReturn([
                    'sub' => 'google123',
                    'given_name' => 'John',
                    'family_name' => 'Doe',
                ]);
        });

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-token'
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['token', 'user', 'profile_complete']
            ]);
    }
}
