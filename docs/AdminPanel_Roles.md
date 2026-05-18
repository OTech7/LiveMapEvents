# Admin Panel — Roles & Permissions

Companion to [`AdminPanel_Plan.md`](./AdminPanel_Plan.md). Documents what's
**actually shipped** for RBAC after Phase 1 + 2, the four canonical roles, and
every supported way to assign or revoke them.

> **Created:** 2026-05-07
> **Backed by:** `spatie/laravel-permission` v6, guard `sanctum`.

---

## 1. What's shipped today (Phase 1 + 2)

Verified against the codebase on 2026-05-07.

### Backend (Laravel)

| File                                                                         | Purpose                                                                                                                                 |
|------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------|
| `backend/routes/admin.php`                                                   | All admin endpoints under `/api/admin/v1/*`.                                                                                            |
| `backend/bootstrap/app.php`                                                  | Mounts the admin route group via `then:`; aliases `role` / `permission` middleware; renders Spatie `UnauthorizedException` as JSON 403. |
| `backend/app/Http/Controllers/Admin/V1/AuthController.php`                   | `GET /me`, `POST /logout`.                                                                                                              |
| `backend/app/Http/Controllers/Admin/V1/HealthController.php`                 | `GET /health`.                                                                                                                          |
| `backend/app/Http/Controllers/Admin/V1/UsersController.php`                  | `GET /users`, `GET /users/{id}`, `PUT /users/{id}` (incl. role sync), `DELETE /users/{id}`.                                             |
| `backend/app/Http/Resources/Admin/AdminUserResource.php`                     | Admin-flavoured user payload (id, roles, ISO timestamps).                                                                               |
| `backend/database/seeders/AdminRoleSeeder.php`                               | Seeds permissions + roles, optionally promotes a user.                                                                                  |
| `backend/config/cors.php`                                                    | Allowlists `localhost:3000` and `admin.live-events-map.tech`.                                                                           |
| `backend/database/migrations/2026_03_09_064520_create_permission_tables.php` | Spatie tables (already shipped).                                                                                                        |

### Admin panel (Next.js, in `/web`)

| Page                                | What it does                                                                   |
|-------------------------------------|--------------------------------------------------------------------------------|
| `src/app/login/page.tsx`            | Phone + OTP sign-in, stores Sanctum token.                                     |
| `src/app/admin/layout.tsx`          | Auth gate, calls `/admin/v1/me`, shows "Not authorized" if `is_admin: false`.  |
| `src/app/admin/page.tsx`            | Dashboard with API health card.                                                |
| `src/app/admin/users/page.tsx`      | Searchable, paginated users table.                                             |
| `src/app/admin/users/[id]/page.tsx` | Edit form with role toggles (assigning `admin` from here grants panel access). |

### Local dev plumbing (untouched on server)

`docker-compose.local.yml` + `.env.local.docker` at the repo root — bring up
Postgres + Redis + Laravel locally on host port 8000. Production deploy
(`deploy-remote.sh`) doesn't ship either of these.

### Bugs caught and fixed in the post-Phase-2 audit (2026-05-07)

- `UsersController::update()` accepted `gender=other` and any string for
  `user_type`. The `users` table has DB-level enums
  (`gender ∈ {male, female}`, `user_type ∈ {attendee, business, admin}`),
  so those values would have passed validation and then crashed on insert.
  Fixed: the validation now mirrors the enums exactly.
- Phone updates didn't validate uniqueness. Now uses
  `Rule::unique('users', 'phone')->ignore($user->id)`.
- Frontend gender dropdown showed an `other` option that didn't exist in the
  DB enum. Removed.
- Frontend `user_type` was a free-text input; now a `<select>` with the three
  valid enum values.
- `AdminRoleSeeder` only printed "no user to promote" with no further hint.
  It now lists the first 10 users so you can see exactly what to pass for
  `ADMIN_SEED_PHONE` / `ADMIN_SEED_USER_ID`. New `ADMIN_SEED_USER_ID` env var
  added — picking by id is more reliable than by phone (no formatting drift).

---

## 2. The four roles

Defined in `AdminRoleSeeder`, all on guard `sanctum`. Each is a bag of the
permissions listed in §3.

| Role              | Permissions                                       | Intended use                                                                                                                                                                                   |
|-------------------|---------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **`admin`**       | All `*.view`, `*.create`, `*.update`, `*.delete`  | Day-to-day operators. **Required to access the admin panel** — the `role:admin` middleware on `routes/admin.php` checks for this exact name.                                                   |
| **`super_admin`** | Same as `admin` today                             | Reserved for future "destructive ops only super-admin can do" (e.g. wiping audit logs, managing other admins). Currently identical to `admin` — not yet enforced separately in any controller. |
| **`editor`**      | All `*.view`, `*.create`, `*.update` (no deletes) | Content moderators. **Note:** the panel currently still requires the `admin` role to load any page beyond `/me`. Editor will become useful once we add per-permission gates in Phase 3.        |
| **`viewer`**      | All `*.view` only                                 | Read-only audit access. Same caveat as editor — not yet wired into the UI.                                                                                                                     |

> **Practical consequence right now:** only `admin` actually unlocks the panel.
> The three other roles exist in the DB and the seeder, but the UI/backend
> don't yet differentiate between them. That work lives in Phase 3 of
> `AdminPanel_Plan.md` (replace the single `role:admin` gate with per-resource
> permission checks driven by the generic `AdminResource` engine).

### Resources covered by permissions

The seeder generates four permissions (`view`/`create`/`update`/`delete`) for each:

```
users, events, venues, pins, interests,
business_verifications, checkins, saved_events,
loyalty_accounts, loyalty_transactions,
vouchers, voucher_batches, vibe_stories,
promotions, transactions, device_tokens,
activity_logs, user_interactions
```

That's 18 resources × 4 actions = **72 permissions**, all under guard `sanctum`.

To add a new resource, append its slug to the `$resources` array in
`AdminRoleSeeder::run()` and re-run the seeder — `findOrCreate` makes it
idempotent.

---

## 3. Where roles live in the database

Spatie writes to **five tables** (created by the
`2026_03_09_064520_create_permission_tables` migration):

| Table                   | Holds                                 | Notes                                                                                    |
|-------------------------|---------------------------------------|------------------------------------------------------------------------------------------|
| `roles`                 | One row per role                      | `name='admin'`, `guard_name='sanctum'` etc.                                              |
| `permissions`           | One row per permission                | `name='users.view'`, `guard_name='sanctum'` etc.                                         |
| `role_has_permissions`  | `(role_id, permission_id)`            | Which permissions each role grants. Managed by the seeder.                               |
| **`model_has_roles`**   | **`(role_id, model_type, model_id)`** | **The table that says "this user has this role."** Add a row to grant, delete to revoke. |
| `model_has_permissions` | Direct grants bypassing roles         | Not used by us — leave empty.                                                            |

### To make a user an admin, you add one row to `model_has_roles`:

| role_id              | model_type        | model_id         |
|----------------------|-------------------|------------------|
| (id of role `admin`) | `App\Models\User` | (id of the user) |

Spatie caches role lookups; after **any direct SQL change**, flush it:

```bash
docker compose -f docker-compose.local.yml exec app \
  php artisan permission:cache-reset
```

(Tinker, the seeder, and the `User::syncRoles()` API all flush automatically.)

---

## 4. How to change a user's role — six options

Pick the one that fits the situation. Local dev examples assume the
`docker-compose.local.yml` stack from the daily-run guide; on the server,
swap `docker compose -f docker-compose.local.yml exec app` for whatever you
use to run artisan there.

### 4.1 From the admin panel UI (easiest, day-to-day)

1. Sign in to http://localhost:3000.
2. **Users** → click the user.
3. Click any role chip in the **Roles** row to toggle it on/off.
4. **Save changes**.

Behind the scenes the panel calls `PUT /api/admin/v1/users/{id}` with
`roles: [...]`, which calls `$user->syncRoles([...])` — i.e. the row set in
`model_has_roles` is replaced wholesale. Empty array = strip all roles.

> **Caveat:** assigning `admin` only succeeds because *you* are already an
> admin (the route is gated by `role:admin`). Don't strip your own admin
> role from the panel — you'll lock yourself out and have to fix it via 4.2
> or 4.4.

### 4.2 Tinker — one-liner (most flexible, can run from the host)

```bash
# grant
docker compose -f docker-compose.local.yml exec app \
  php artisan tinker --execute="App\Models\User::find(1)->assignRole('admin');"

# revoke
docker compose -f docker-compose.local.yml exec app \
  php artisan tinker --execute="App\Models\User::find(1)->removeRole('admin');"

# replace all roles atomically (e.g. demote admin → editor)
docker compose -f docker-compose.local.yml exec app \
  php artisan tinker --execute="App\Models\User::find(1)->syncRoles(['editor']);"

# strip all roles
docker compose -f docker-compose.local.yml exec app \
  php artisan tinker --execute="App\Models\User::find(1)->syncRoles([]);"

# look up by phone first
docker compose -f docker-compose.local.yml exec app \
  php artisan tinker --execute="App\Models\User::where('phone','+491783013048')->first()->assignRole('admin');"

# list everyone with the admin role
docker compose -f docker-compose.local.yml exec app \
  php artisan tinker --execute="App\Models\User::role('admin')->get(['id','phone'])->each(fn(\$u)=>print(\$u->id.' '.\$u->phone.PHP_EOL));"
```

### 4.3 The `AdminRoleSeeder`

Runs the full role + permission setup, then promotes one user to `admin`.

```bash
# pick by user id (most reliable — no phone formatting issues)
docker compose -f docker-compose.local.yml exec app \
  sh -c 'ADMIN_SEED_USER_ID=1 php artisan db:seed --class=AdminRoleSeeder'

# or by exact phone string
docker compose -f docker-compose.local.yml exec app \
  sh -c 'ADMIN_SEED_PHONE="+491783013048" php artisan db:seed --class=AdminRoleSeeder'

# no env var → falls back to the first user in the table (dev convenience)
docker compose -f docker-compose.local.yml exec app \
  php artisan db:seed --class=AdminRoleSeeder
```

If it can't find a match, the seeder now **prints the first 10 users in the
DB** so you can see what to pass.

### 4.4 Tinker interactive shell

For when you want to poke around:

```bash
docker compose -f docker-compose.local.yml exec app php artisan tinker
```

```php
$u = App\Models\User::where('phone', '+491783013048')->first();
$u->getRoleNames();             // current roles
$u->assignRole('admin');
$u->syncRoles(['editor', 'viewer']);
$u->removeRole('admin');
$u->hasRole('admin');           // bool
$u->getAllPermissions()->pluck('name');
exit
```

### 4.5 Direct SQL (IntelliJ Database tool, etc.)

Useful when you've locked yourself out and can't auth into the panel.

**Grant `admin` to user with phone `+491783013048`:**

```sql
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\Models\User', u.id
FROM   roles r, users u
WHERE  r.name = 'admin'
  AND  r.guard_name = 'sanctum'
  AND  u.phone = '+491783013048'
ON CONFLICT DO NOTHING;
```

**Revoke `admin`:**

```sql
DELETE FROM model_has_roles
WHERE role_id = (SELECT id FROM roles WHERE name='admin' AND guard_name='sanctum')
  AND model_type = 'App\Models\User'
  AND model_id   = (SELECT id FROM users WHERE phone='+491783013048');
```

**See who has what:**

```sql
SELECT u.id, u.phone, COALESCE(STRING_AGG(r.name, ','), '(none)') AS roles
FROM   users u
LEFT JOIN model_has_roles mhr
       ON mhr.model_id = u.id AND mhr.model_type = 'App\Models\User'
LEFT JOIN roles r ON r.id = mhr.role_id
GROUP BY u.id, u.phone
ORDER BY u.id;
```

After **any direct SQL** mutation, flush Spatie's cache so live requests see
the change:

```bash
docker compose -f docker-compose.local.yml exec app \
  php artisan permission:cache-reset
```

### 4.6 The HTTP API directly (curl / Postman / IntelliJ HTTP client)

Same path the panel uses. Requires a Sanctum bearer token from an existing
admin.

```http
PUT /api/admin/v1/users/1
Authorization: Bearer <token>
Content-Type: application/json

{ "roles": ["admin"] }
```

The body is the **complete** role set — `["admin","editor"]` keeps both,
`[]` strips everything. Returns `200` with the updated user resource on
success; `403` with `messages.forbidden` if the caller isn't admin.

---

## 5. Verifying a change

```bash
# inspect a user's current roles
docker compose -f docker-compose.local.yml exec app \
  php artisan tinker --execute="App\Models\User::find(1)->getRoleNames()->dump();"

# from the panel's perspective — does /me say is_admin: true?
TOKEN=...                        # a Sanctum token for that user
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/admin/v1/me
```

If the user has `admin` but the panel still says "Not authorized," the
Spatie cache is stale — flush it (see §3).

---

## 6. Open follow-ups (Phase 3+)

- Replace the single `role:admin` gate in `routes/admin.php` with per-resource
  `permission:` middleware so `editor` and `viewer` actually get used.
- Add a `Roles` page in the admin panel for editing role↔permission mappings
  without having to touch the seeder.
- Audit log the role mutations (planned: hook into the existing
  `activity_logs` model in Phase 5 of the plan).
- 2FA for any account that holds `admin` or `super_admin`.
