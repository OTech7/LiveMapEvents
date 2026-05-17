<?php

namespace Tests\Unit\Actions;

use App\Actions\GenerateToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_and_returns_a_plain_text_token(): void
    {
        $user = User::create(['phone' => '+963911000001']);
        $action = app(GenerateToken::class);

        $token = $action->handle($user);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    public function test_handle_stores_token_in_database(): void
    {
        $user = User::create(['phone' => '+963911000002']);
        $action = app(GenerateToken::class);

        $action->handle($user);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'mobile',
        ]);
    }

    public function test_handle_returns_different_token_on_each_call(): void
    {
        $user = User::create(['phone' => '+963911000003']);
        $action = app(GenerateToken::class);

        $token1 = $action->handle($user);
        $token2 = $action->handle($user);

        $this->assertNotSame($token1, $token2);
    }

    public function test_handle_token_contains_pipe_separator(): void
    {
        // Sanctum plain-text tokens are formatted as "{id}|{secret}"
        $user = User::create(['phone' => '+963911000004']);
        $action = app(GenerateToken::class);

        $token = $action->handle($user);

        $this->assertStringContainsString('|', $token);
    }
}
