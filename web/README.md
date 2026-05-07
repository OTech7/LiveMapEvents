# LiveMapEvents — Admin Panel (`/web`)

Next.js 14 + TypeScript + Tailwind. Talks to the existing Laravel API at
`/api/admin/v1/*` (auth + users), and reuses the public phone-OTP flow at
`/api/v1/auth/phone/*` for sign-in.

This is the implementation of [`docs/AdminPanel_Plan.md`](../docs/AdminPanel_Plan.md)
— Phase 1 + Phase 2 (skeleton + login + Users CRUD).

---

## Run it locally

### 1. Make sure the Laravel backend is reachable

The admin panel does not bring up the backend itself — it just talks to it.
Pick whichever way you normally run Laravel:

| Option                                  | URL the panel will hit             |
|-----------------------------------------|------------------------------------|
| `php artisan serve` from `/backend`     | `http://localhost:8000`            |
| `docker compose up` (uses Caddy on :80) | `http://localhost`                 |
| Production API directly                 | `https://api.live-events-map.tech` |

### 2. Apply the new backend migrations & seed admin role

The admin module added one seeder. From `/backend`:

```bash
php artisan migrate                    # if you haven't already
php artisan db:seed --class=AdminRoleSeeder
```

To grant the `admin` role to a specific phone number:

```bash
ADMIN_SEED_PHONE=+9477xxxxxxx php artisan db:seed --class=AdminRoleSeeder
```

Otherwise the seeder grants `admin` to the first user in the table — fine
for local dev, **don't** rely on this in prod.

### 3. Start the panel

```bash
cd web
cp .env.local.example .env.local       # then edit if your API isn't on :8000
npm install
npm run dev
```

Open **http://localhost:3000**.

---

## How the local URL plumbing works

```
 browser  ──►  http://localhost:3000          (Next.js dev server)
                  │
                  │  any request to /api/*
                  ▼
              Next.js rewrite (next.config.js)
                  │
                  ▼
              http://localhost:8000/api/*     (Laravel)
```

The browser never crosses an origin, so **CORS doesn't apply in dev**.
Bearer tokens are stored in `localStorage` under `livemap.admin.token`.

In production, the panel would be served from
`https://admin.live-events-map.tech` and call
`https://api.live-events-map.tech/api/admin/v1/*` directly — that's why
`backend/config/cors.php` allowlists both origins.

---

## Sign-in flow

1. Enter your phone (e.g. `+9477xxxxxxx`) — calls `POST /api/v1/auth/phone/request-otp`.
2. Enter the OTP — calls `POST /api/v1/auth/phone/verify-otp`, gets a Sanctum token,
   stores it in `localStorage`.
3. Panel loads → calls `GET /api/admin/v1/me`.
    - If `is_admin: true` → shell + dashboard.
    - If `is_admin: false` → "Not authorized" page (account exists, no admin role).
    - If 401 → token cleared, bounced to `/login`.

For local dev, the `OTP_FAKE=true` / `OTP_FAKE_CODE=000000` env vars in
`.env.docker` make any phone work with the code `000000`.

---

## Layout

```
web/
├─ src/
│  ├─ app/
│  │  ├─ layout.tsx           # root html, providers
│  │  ├─ page.tsx             # / → bounces to /admin or /login
│  │  ├─ globals.css
│  │  ├─ login/page.tsx
│  │  └─ admin/
│  │     ├─ layout.tsx        # auth gate + sidebar shell
│  │     ├─ page.tsx          # dashboard
│  │     └─ users/
│  │        ├─ page.tsx       # list + search + pagination
│  │        └─ [id]/page.tsx  # detail + edit + roles + delete
│  ├─ components/
│  │  └─ sidebar.tsx
│  └─ lib/
│     ├─ api/client.ts        # fetch wrapper
│     ├─ auth/session.ts      # token in localStorage
│     └─ providers.tsx        # react-query
├─ next.config.js             # /api/* rewrite to backend
├─ tailwind.config.ts
├─ tsconfig.json
└─ package.json
```

## Adding a new resource

The plan calls for a generic `AdminResource` engine (Phase 3) that turns one
backend file into a fully working CRUD page. Until that lands, the pattern
mirrored from `users` is:

1. **Backend** — `app/Http/Controllers/Admin/V1/<Name>Controller.php`,
   `app/Http/Resources/Admin/<Name>Resource.php`, routes in `routes/admin.php`
   inside the `role:admin` group.
2. **Frontend** — `src/app/admin/<name>/page.tsx` (list) and
   `src/app/admin/<name>/[id]/page.tsx` (detail). Copy-paste from `users`.
3. **Sidebar** — flip the `disabled: true` flag on the matching entry in
   `src/components/sidebar.tsx`.

## Useful scripts

```bash
npm run dev         # http://localhost:3000 with hot reload
npm run build       # production build
npm run typecheck   # tsc --noEmit
npm run gen:api     # regenerate src/lib/api/openapi.d.ts from /docs
```
