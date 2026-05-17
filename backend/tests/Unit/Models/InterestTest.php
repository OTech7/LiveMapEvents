<?php

namespace Tests\Unit\Models;

use App\Models\Interest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterestTest extends TestCase
{
    use RefreshDatabase;

    // ─── getRouteKeyName() ────────────────────────────────────────────────────

    public function test_route_key_name_is_slug(): void
    {
        $interest = new Interest();

        $this->assertSame('slug', $interest->getRouteKeyName());
    }

    public function test_route_model_binding_resolves_by_slug(): void
    {
        Interest::create(['name' => 'Music', 'slug' => 'music']);

        $found = Interest::where('slug', 'music')->first();

        $this->assertNotNull($found);
        $this->assertSame('music', $found->slug);
    }

    // ─── users() relationship ─────────────────────────────────────────────────

    public function test_users_relationship_returns_all_attached_users(): void
    {
        $interest = Interest::create(['name' => 'Sports', 'slug' => 'sports']);
        $user1 = User::create(['phone' => '+963911000001']);
        $user2 = User::create(['phone' => '+963911000002']);

        $user1->interests()->attach($interest->id);
        $user2->interests()->attach($interest->id);

        $this->assertCount(2, $interest->users);
    }

    public function test_users_relationship_returns_empty_when_no_users_attached(): void
    {
        $interest = Interest::create(['name' => 'Art', 'slug' => 'art']);

        $this->assertCount(0, $interest->users);
    }

    // ─── fillable fields ──────────────────────────────────────────────────────

    public function test_interest_can_be_created_with_name_and_slug(): void
    {
        $interest = Interest::create(['name' => 'Gaming', 'slug' => 'gaming']);

        $this->assertDatabaseHas('interests', ['name' => 'Gaming', 'slug' => 'gaming']);
    }

    public function test_slug_is_unique(): void
    {
        Interest::create(['name' => 'Music', 'slug' => 'music']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Interest::create(['name' => 'Music 2', 'slug' => 'music']);
    }
}
