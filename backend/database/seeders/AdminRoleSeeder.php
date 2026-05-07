<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the canonical admin roles and assigns the `admin` role to a
 * specific user (configured via the ADMIN_SEED_PHONE env var, falling
 * back to the first user in the database for local dev convenience).
 *
 * Usage:
 *   php artisan db:seed --class=AdminRoleSeeder
 *
 * To promote a specific phone:
 *   ADMIN_SEED_PHONE=+9477000000 php artisan db:seed --class=AdminRoleSeeder
 */
class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Spatie caches permissions — clear so re-seeding picks up new ones.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'sanctum';

        // ── Permissions (resource.action) ────────────────────────────
        // Keep this list in sync with the resources surfaced by the panel.
        $resources = [
            'users', 'events', 'venues', 'pins', 'interests',
            'business_verifications', 'checkins', 'saved_events',
            'loyalty_accounts', 'loyalty_transactions',
            'vouchers', 'voucher_batches', 'vibe_stories',
            'promotions', 'transactions', 'device_tokens',
            'activity_logs', 'user_interactions',
        ];

        foreach ($resources as $resource) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                Permission::findOrCreate("{$resource}.{$action}", $guard);
            }
        }

        // ── Roles ────────────────────────────────────────────────────
        $admin = Role::findOrCreate('admin', $guard);
        $superAdmin = Role::findOrCreate('super_admin', $guard);
        $editor = Role::findOrCreate('editor', $guard);
        $viewer = Role::findOrCreate('viewer', $guard);

        $admin->syncPermissions(Permission::where('guard_name', $guard)->get());
        $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());

        $editor->syncPermissions(
            Permission::where('guard_name', $guard)
                ->where(function ($q) {
                    $q->where('name', 'like', '%.view')
                        ->orWhere('name', 'like', '%.update')
                        ->orWhere('name', 'like', '%.create');
                })
                ->get()
        );

        $viewer->syncPermissions(
            Permission::where('guard_name', $guard)
                ->where('name', 'like', '%.view')
                ->get()
        );

        // ── Promote a user ──────────────────────────────────────────
        // Resolution order:
        //   1. ADMIN_SEED_USER_ID (exact match by id) — most reliable
        //   2. ADMIN_SEED_PHONE (exact match by phone column)
        //   3. fallback to the first user in the table (dev convenience)
        $userId = env('ADMIN_SEED_USER_ID');
        $phone = env('ADMIN_SEED_PHONE');

        $target = match (true) {
            $userId !== null => User::find($userId),
            $phone !== null => User::where('phone', $phone)->first(),
            default => User::orderBy('id')->first(),
        };

        if ($target) {
            $target->assignRole('admin');
            $this->command?->info("Granted admin role to user #{$target->id} ({$target->phone})");
            return;
        }

        // Couldn't find anyone — print a useful breadcrumb instead of a
        // dead-end message. Show what's actually in the users table so the
        // operator can see the right value to pass.
        $this->command?->warn('No matching user to promote.');

        $count = User::count();
        if ($count === 0) {
            $this->command?->line(
                '  Users table is empty. Sign in once via the admin panel ' .
                '(http://localhost:3000) to create your user, then re-run.'
            );
            return;
        }

        $this->command?->line("  Users in DB ({$count}):");
        User::orderBy('id')
            ->limit(10)
            ->get(['id', 'phone'])
            ->each(fn($u) => $this->command?->line(
                sprintf('   id=%d  phone=%s', $u->id, $u->phone ?? '(null)')
            ));
        if ($count > 10) {
            $this->command?->line('   …and ' . ($count - 10) . ' more.');
        }
        $this->command?->line('');
        $this->command?->line(
            '  Re-run with one of:  ADMIN_SEED_USER_ID=1  /  ADMIN_SEED_PHONE="<exact value>"'
        );
    }
}
