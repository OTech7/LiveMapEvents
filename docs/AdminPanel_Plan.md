# LiveMapEvents — Admin Panel Plan

**Status:** Proposal / not yet implemented
**Owner:** omar
**Created:** 2026-05-06
**Module location:** `/web` (new top-level workspace, currently empty)

---

## 1. Goal

Build a web-based admin panel that lets a privileged user view and manage every
table the API touches (users, events, pins, venues, interests, business
verifications, loyalty, vouchers, checkins, vibe stories, etc.) and stays easy
to extend whenever a new feature/table is added later — ideally by writing a
single new module file rather than touching three layers.

The panel must:

1. Authenticate against the existing Laravel + Sanctum stack (same accounts,
   no parallel user store).
2. Reuse the existing OpenAPI spec at `https://api.live-events-map.tech/docs`
   wherever possible so we don't reinvent DTOs.
3. Slot into the existing Caddy + docker-compose deployment without rewriting
   `deploy-remote.sh`.
4. Be fast to add new "Resource X" pages — target: one config file + one
   migration to expose a new table, no React rewrites.

---

## 2. Current state (verified 2026-05-06)

**Backend** — Laravel 12 / PHP 8.2, Sanctum, l5-swagger, Predis. 11 OpenAPI
paths today, all under `/api/v1/*`: auth (OTP + Google), `me`,
`profile` (+avatar, +discovery-settings, +interests CRUD), `interests`
catalog, `pins/nearby`. Schemas: `User`, `Interest`, `AuthResponse`,
`ApiResponse`, `ErrorResponse`.

**Models already migrated** but not yet exposed via API: `Event`, `Venue`,
`BusinessVerification`, `Pin`, `Checkin`, `SavedEvent`, `UserInteraction`,
`LoyaltyAccount`, `LoyaltyTransaction`, `VibeStory`, `Promotion`, `Voucher`,
`VoucherBatch`, `Transaction`, `ActivityLog`, `DeviceToken`. The admin panel
is the natural place to surface all of these.

**RBAC** — `2026_03_09_064520_create_permission_tables.php` (Spatie) is
already migrated, so we have `roles`, `permissions`, `model_has_*` tables ready
to use. No admin role has been seeded yet.

**`/web` folder** — exists but effectively empty (only a 0-byte placeholder
`web/src/app/test` and two empty `web/src/lib/{api,auth}` directories). Free
to fill in.

**Caddy** — already terminates TLS for `live-events-map.tech` (frontend) and
`api.live-events-map.tech` (Laravel). Adding `admin.live-events-map.tech` is
one block + one DNS record.

**Deploy** — `deploy-remote.sh` tars the local working tree and SCPs it.
Wiping/extracting `web/` is already safe under the patched flow.

---

## 3. Recommended stack

### 3.1 Frontend — Next.js 14 (App Router) + TypeScript + Tailwind + shadcn/ui

Why this and not the alternatives:

- **Next.js 14 App Router**: file-based routing maps cleanly onto a
  per-resource module pattern (`/app/(admin)/users/...`, `/app/(admin)/events/...`).
  Server Components let us keep the auth token out of the client bundle.
- **TypeScript**: types are auto-generated from the existing OpenAPI spec
  (see §5) so frontend and backend never drift.
- **Tailwind + shadcn/ui**: lots of polished primitives (Table, DataTable,
  Form, Dialog) without buying into a heavyweight component lib that we'd
  have to fight to extend.
- **TanStack Query** for server state, **TanStack Table** for grids — these
  are the de-facto admin-panel pair and handle the CRUD/pagination/filtering
  patterns we'll repeat on every resource.

Alternatives considered and rejected:

| Option                    | Why not                                                                                                                                                                                       |
|---------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Filament (Laravel-native) | Couples admin to Blade/Livewire; user explicitly asked for a separate `web/` module with its own frontend.                                                                                    |
| React Admin / Refine.dev  | Faster bootstrap but heavier conventions; harder to escape when a screen needs a non-CRUD layout (e.g. PostGIS map preview for pins/events). Worth revisiting if the panel stays purely CRUD. |
| Pure Vite + React SPA     | Fine, but loses SSR auth and we'd hand-roll routing. Next.js gives more for similar effort.                                                                                                   |

### 3.2 Backend — extend the existing Laravel app, do **not** spin up a second backend

The admin panel needs the same Eloquent models, the same Postgres/PostGIS
database, the same Sanctum tokens. Building a separate backend service would
mean duplicating models or wiring a second API client across services — pure
churn.

Instead: add an **Admin module inside the existing Laravel app**, isolated by
folder, route prefix, middleware, and permissions:

```
backend/
  routes/
    api.php          # public mobile API (unchanged)
    admin.php        # NEW — all admin endpoints, prefixed /api/admin/v1
  app/
    Http/Controllers/
      Admin/V1/
        UsersController.php
        EventsController.php
        ...
    Modules/
      Admin/
        AdminServiceProvider.php   # boots routes/admin.php + permissions
        Resources/                 # one file per resource (see §4)
```

Mounted in `bootstrap/app.php`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    then: function () {
        Route::prefix('api/admin/v1')
            ->middleware(['api', 'auth:sanctum', 'role:admin'])
            ->group(base_path('routes/admin.php'));
    },
    ...
)
```

Why one app, not two:

- One source of truth for migrations, models, OpenAPI, queues, mailers.
- Sanctum tokens issued by the public API just work — login flow can be
  reused (admin user logs in with phone OTP or Google like everyone else,
  panel just checks for `role:admin`).
- Deploy stays a single tarball.

---

## 4. Module architecture (the "extensible per-feature" piece)

The repeated cost in admin panels is "another table → another five files."
We collapse that to **one resource file on the backend + one route folder on
the frontend**, both driven by the same shape.

### 4.1 Backend — Admin Resource convention

Each resource is a single class describing how it's listed, filtered, edited,
and authorized. Example skeleton (pseudocode):

```php
// app/Modules/Admin/Resources/EventResource.php
class EventResource extends AdminResource
{
    public string $model = \App\Models\Event::class;
    public string $route = 'events';                  // → /api/admin/v1/events
    public string $permission = 'events';             // → events.view / .create / .update / .delete

    public array $listColumns   = ['id','title','starts_at','venue.name','status'];
    public array $searchable    = ['title','description'];
    public array $filters       = ['status','venue_id'];
    public array $editable      = ['title','description','starts_at','ends_at','venue_id','status'];

    public array $relations     = ['venue','interests'];   // eager-loaded on show
    public array $rules = [
        'title'      => 'required|string|max:255',
        'starts_at'  => 'required|date',
        'ends_at'    => 'nullable|date|after:starts_at',
        'venue_id'   => 'required|exists:venues,id',
    ];
}
```

A single `AdminResourceController` reads the resource definition and serves
the standard verbs (`index`, `show`, `store`, `update`, `destroy`) plus a
`/schema` endpoint that returns the resource's editable columns + validation
rules + relations as JSON. The frontend uses `/schema` to auto-render forms.

Adding a new resource = drop a new `XyzResource.php` file. The
`AdminServiceProvider` auto-discovers everything in `Modules/Admin/Resources/`
and registers routes + OpenAPI annotations.

### 4.2 Frontend — file-based mirror

```
web/
  src/
    app/
      (auth)/login/page.tsx
      (admin)/
        layout.tsx                    # nav, breadcrumbs, RBAC guard
        page.tsx                      # dashboard (counts, recent activity)
        [resource]/                   # dynamic — one folder for ALL resources
          page.tsx                    # generic list view (uses /schema + /index)
          new/page.tsx                # generic create form
          [id]/page.tsx               # generic detail/edit form
        events/page.tsx               # OPTIONAL override when generic isn't enough
        pins/map/page.tsx             # custom map view for PostGIS pins
    lib/
      api/client.ts                   # fetch wrapper, auth header, error handling
      api/openapi.d.ts                # auto-generated from /docs (openapi-typescript)
      auth/session.ts
      admin/resource-loader.ts        # reads /api/admin/v1/<r>/schema
    components/
      data-table.tsx                  # TanStack Table wrapper used by every list
      auto-form.tsx                   # generic form driven by /schema rules
```

**Adding a new feature** therefore requires:

1. New migration + model in `backend/` (already required, nothing new).
2. One new `XyzResource.php` in `app/Modules/Admin/Resources/`.
3. (Optional) custom override page under `web/src/app/(admin)/<route>/page.tsx`
   only if the generic list/edit isn't enough.

For 90% of tables, step 3 is skipped entirely.

### 4.3 Permissions & audit

- Spatie roles already migrated — seed `admin`, `super_admin`, `editor`, `viewer`.
- Each resource maps to four permissions: `<name>.view|create|update|delete`.
- Hook a `LogsActivity` trait (or use the existing `ActivityLog` model) on
  every resource controller so every admin write lands in `activity_logs`.
  Surface that as a built-in "Audit log" page in the panel.

---

## 5. OpenAPI as the contract

The l5-swagger generation already runs on every container boot
(`backend/docker/entrypoint.sh`). We extend it:

1. **Backend** — annotate `Admin\V1\*Controller` methods with `@OA\*` attributes
   the same way `Api\V1` is done today. Generated spec lives next to the
   existing one, available at `https://api.live-events-map.tech/docs`.
2. **Frontend** — add an `npm run gen:api` script that runs
   [`openapi-typescript`](https://github.com/openapi-ts/openapi-typescript)
   against the live `/docs` URL and writes `web/src/lib/api/openapi.d.ts`.
   CI runs it on every build so types never drift from the API.
3. The generic `AutoForm` and `DataTable` components key off these types, so
   adding a new schema in the backend lights up typed pages on the frontend
   automatically.

---

## 6. Deployment integration

### 6.1 New `web` service in `docker-compose.yml`

```yaml
web:
  build:
    context: ./web
    dockerfile: Dockerfile          # multi-stage: deps → build → runtime
  container_name: livemap-web
  restart: unless-stopped
  expose:
    - "3000"
  env_file:
    - .env.docker
  environment:
    NEXT_PUBLIC_API_BASE_URL: https://api.live-events-map.tech/api/admin/v1
  depends_on:
    - app
  networks:
    - livemap
```

The service exposes only port 3000 inside the docker network — Caddy is the
only thing that talks to it.

### 6.2 New Caddy block

Add to `caddy/Caddyfile`:

```caddyfile
admin.live-events-map.tech {
    encode zstd gzip
    reverse_proxy web:3000 {
                               header_up X-Real-IP {remote_host}
                               header_up X-Forwarded-For {remote_host}
                               header_up X-Forwarded-Proto {scheme}
                           }
    log { output stdout; format console }
}
```

Plus a DNS A record: `admin.live-events-map.tech → 187.124.180.7`.
Let's Encrypt will issue the cert on first boot.

### 6.3 Deploy script

`deploy-remote.sh` tars the working tree and wipes `backend/` + `mobile/` on
the server before extracting. Patch the script to also include `web/` in the
tarball and the wipe list. No other changes.

---

## 7. Phased delivery

A 5-phase plan so we have something usable after Phase 2 and the rest is
incremental.

**Phase 1 — Skeleton (1 day).**
Scaffold `web/` with Next.js + TypeScript + Tailwind + shadcn/ui. Add empty
`backend/routes/admin.php`, `Admin\V1\HealthController` returning `{ ok: true }`.
Wire docker-compose `web` service + Caddy `admin.` block. Ship and verify
TLS issuance.

**Phase 2 — Auth + dashboard + Users (2 days).**
Reuse phone-OTP / Google login from the existing API. Seed `admin` role,
gate `routes/admin.php` with `role:admin`. Build the first resource
(`UsersResource`) end-to-end through the generic mechanism so the patterns
are real, not hypothetical. Add a basic dashboard with row counts.

**Phase 3 — Generic resource engine (2 days).**
Land `AdminResource`, `AdminResourceController`, and the `/schema` endpoint.
On the frontend, land `DataTable` and `AutoForm`. Port `UsersResource` from
Phase 2 onto the generic engine to prove it.

**Phase 4 — Roll out remaining resources (3–5 days).**
Add a resource file per table: Events, Venues, Pins, Interests,
BusinessVerifications, Checkins, SavedEvents, Loyalty (Accounts +
Transactions), Vouchers (+ Batches), VibeStories, Promotions,
DeviceTokens, ActivityLog (read-only), UserInteractions (read-only).
Add bespoke views where generic isn't enough — likely Pins/Events on a
PostGIS-backed map.

**Phase 5 — Hardening (1–2 days).**

- Audit log surfaced in UI.
- 2FA for admin users (optional).
- Address the deferred security items already tracked in memory (TrustProxies
  is done; CORS + Sanctum allowlist for `admin.live-events-map.tech`,
  basic-auth or disable `/api/documentation` in prod, swap `OTP_FAKE` off).
- Rate-limit the admin endpoints differently from the public API.

---

## 8. Open questions for omar

1. Do you want one panel for everything or eventually split "operator" vs
   "business owner" panels? (Affects whether we add a tenant scope now.)
2. PostGIS data: do you want a Leaflet/Mapbox map view for pins and events,
   or is a tabular list with lat/lng enough for v1?
3. Bulk actions (export CSV, bulk-delete)? Cheap to add inside the generic
   `DataTable`, but only if you want them.
4. Should the admin panel be reachable only from a VPN/IP allowlist, or
   public-with-2FA?

---

## 9. File-level checklist (execution order)

```
[ ] caddy/Caddyfile                         # add admin.live-events-map.tech block
[ ] DNS                                     # A record admin.live-events-map.tech
[ ] docker-compose.yml                      # add `web` service
[ ] deploy-remote.sh                        # include web/ in tar + wipe list
[ ] web/package.json + tsconfig + tailwind  # Next.js scaffold
[ ] web/Dockerfile                          # multi-stage build
[ ] web/src/app/(auth)/login/page.tsx
[ ] web/src/app/(admin)/layout.tsx
[ ] web/src/app/(admin)/page.tsx            # dashboard
[ ] web/src/app/(admin)/[resource]/page.tsx        # generic list
[ ] web/src/app/(admin)/[resource]/[id]/page.tsx   # generic edit
[ ] web/src/lib/api/client.ts
[ ] web/src/lib/api/openapi.d.ts            # generated
[ ] web/src/components/data-table.tsx
[ ] web/src/components/auto-form.tsx
[ ] backend/routes/admin.php
[ ] backend/app/Modules/Admin/AdminServiceProvider.php
[ ] backend/app/Modules/Admin/AdminResource.php
[ ] backend/app/Http/Controllers/Admin/V1/AdminResourceController.php
[ ] backend/app/Modules/Admin/Resources/UserResource.php
[ ] backend/database/seeders/AdminRoleSeeder.php
[ ] backend/bootstrap/app.php               # register admin route group
[ ] backend/composer.json                   # spatie/laravel-permission already installed?
```
