# LiveEventsMap — Full-Stack Refactoring Plan

**Date:** 2026-05-24
**Scope:** Backend (Laravel 12), Web (Next.js 14), Mobile (Flutter), Infra (Docker/Caddy/Deploy)
**Goal:** Prioritized, verified list of refactoring issues across code quality, architecture, performance, and security.
Approve the items you want fixed and I'll execute them.

> **Note on prior work:** A previous pass (`backend/REFACTORING_REPORT.md`) already addressed enums, race conditions,
> geo bounds validation, InterestService/ProfileService extraction, PromotionPolicy, audit logging, and PinService cache
> keys. Those items are **excluded** from this plan.

---

## What "Refactoring" Means in This Plan

The plan groups changes into four dimensions, applied to all four layers of the stack:

| Dimension        | What it means                                                                                         |
|------------------|-------------------------------------------------------------------------------------------------------|
| **Code quality** | Naming, dead code, magic numbers, method length, consistent error handling.                           |
| **Architecture** | Service/policy/repository boundaries, DTOs, module structure, separation of concerns.                 |
| **Performance**  | N+1 queries, missing indexes, cache key strategy, eager loading.                                      |
| **Security**     | Secrets handling, authorization gaps, mass assignment, validation, rate limiting, transport security. |

Each finding includes: severity (P0/P1/P2/P3), file reference, what to change, why.

---

## P0 — Fix Immediately (Security / Data Loss Risk)

### S-1. Production secrets exposed in git history

**Files:** `.env.docker` (now untracked, but present in commit `78137b5` and earlier — `7c384ab`, `dc9a4a6`).
**Verified.** The file was committed with real production credentials and later untracked via
`78137b5 Untrack .env.docker — secrets file should not be in git`. The secrets remain recoverable from `git log` on any
clone:

- `APP_KEY` (Laravel encryption key — `base64:...` value redacted)
- `DB_PASSWORD` (Aiven PostgreSQL password — redacted)
- `REDIS_PASSWORD` (redacted)
- `ULTRAMSG_TOKEN` (redacted)

> The literal values that were leaked are intentionally NOT reproduced here so this
> document itself doesn't trip secret scanners. Look them up in
> `git show 78137b5^:.env.docker` (or any earlier commit that still contained the
> file) — and rotate every one of them before doing anything else.

**Action:**

1. **Rotate all four secrets today** in Aiven, Redis, UltraMsg, and run `php artisan key:generate` for a new `APP_KEY`.
   Re-encrypt any data that used the old key (sessions, cookies — anything `Crypt::` touched).
2. Rewrite git history with `git filter-repo` (preferred over `bfg`) to remove `.env.docker` from all commits.
   Force-push and require everyone to re-clone.
3. Add a pre-commit hook (e.g. `gitleaks`) to prevent re-occurrence.

### S-2. Hardcoded Google Maps API keys in mobile app — DEFERRED (2026-05-24)

**Files:**

- `mobile/ios/Runner/AppDelegate.swift:11` — `AIzaSyBCm10xMuTCaln_X-Ysb-dMn56dVyex9tQ`
- `mobile/android/app/src/main/AndroidManifest.xml:34` — `AIzaSyBhoqBnj_k5cPu8PfPd3ZIClFU15QWMQCQ`

**Status:** REVERTED — the build-time injection wiring (xcconfig / `local.properties` / `manifestPlaceholders`) was
rolled back at the user's request because the iOS side needed manual Xcode project edits to finish. The keys are back to
being hardcoded.

**Both keys remain exposed in git history** regardless of the revert. Minimum compensating controls you should still
apply:

1. **Restrict both keys in the GCP Console** to your iOS bundle ID and Android SHA-1 fingerprint. This makes the keys
   useless to anyone who steals them from git.
2. Rotate when convenient — the prior keys are public.
3. Revisit S-2 when you're ready to do the one-time Xcode setup; the original plan steps (xcconfig + `local.properties`)
   remain valid.

### S-3. Mobile auth token stored in plaintext SharedPreferences

**File:** `mobile/lib/core/network/interceptor.dart` (token retrieval) and wherever the AuthRepository writes it.
A stolen device or a malicious app on a rooted/jailbroken phone can read the Bearer token directly.

**Action:** Migrate to `flutter_secure_storage` — Keychain on iOS, EncryptedSharedPreferences on Android. Wrap behind a
`TokenStorage` interface so the rest of the code is unchanged.

### S-4. User model exposes PII in every response

**File:** `backend/app/Models/User.php:56-57`
`$hidden = []`. Every API response that returns a User includes `phone`, `google_id`, `dob`, and the raw `location`
Point. The `UserResource` may filter some of this, but any place the model serializes directly (e.g. `auth()->user()` in
a closure response) leaks PII.

**Action:**

```php
protected $hidden = ['google_id', 'remember_token'];
```

Plus: audit `UserResource` to ensure `phone` is only emitted when the requester *is* the user, and `location` is never
returned as raw coordinates (return at venue/pin resolution instead).

### S-5. Authorization missing on Business `show()`

**File:** `backend/app/Http/Controllers/Api/V1/Business/PromotionController.php:46-52`
**Verified.** `update()` (line 56) and `destroy()` (line 68) call `$this->authorize(...)`; `show()` does not. A business
owner can fetch the full record of any other owner's promotion by guessing IDs.

**Action:** Add `$this->authorize('view', $promotion);` to `show()` and implement a `view` method on `PromotionPolicy`
that mirrors the ownership check used by `update`.

---

## P1 — High Priority (Within the Sprint)

### A-1. Missing authorization policies for Venue, Event, VibeStory

**File:** `backend/app/Policies/` — only `PromotionPolicy.php` exists.
Wherever business owners CRUD their venues, events, or stories there's no policy guarding ownership. The pattern is
established for Promotions; replicate it for the other owned entities.

**Action:** Create `VenuePolicy`, `EventPolicy`, `VibeStoryPolicy`. Register in `AppServiceProvider`. Replace any inline
`abort_if($x->owner_id !== auth()->id(), 403)` patterns with `$this->authorize('update', $x)`.

### P-1. Missing index on `venues.owner_id`

**File:** `backend/database/migrations/2026_03_05_000007_create_venues_table.php:13`
**Verified.** `foreignId('owner_id')->constrained(...)` adds the FK constraint, but **PostgreSQL does not auto-index FKs
** (only MySQL does). Queries like "all venues for owner X" will full-scan as the table grows.

**Action:** New migration:

```php
Schema::table('venues', fn (Blueprint $t) => $t->index('owner_id'));
```

Audit every other FK in the migrations directory for the same gap.

### P-2. Lazy expiry pattern is a performance footgun

**File:** `backend/app/Services/PromotionClaimService.php:149` (`getMyClaims()`)
Iterates and updates expired claims on every list call. With many users having many old claims, this scales O(claims)
per page-view.

**Action:** Move expiry to a scheduled job (`app/Console/Commands/ExpireStaleClaims.php`, run hourly via
`app/Console/Kernel.php`). Use one bulk `UPDATE WHERE expires_at < NOW() AND status = 'claimed'`.

### S-6. Deploy scripts lack defensive bash settings

**Files:** `deploy.sh:15`, `deploy-remote.sh:17`
Both use `set -e` but not `set -u` (undefined variables) or `set -o pipefail` (pipe failures). An empty
`$SERVER_DEPLOY_PATH` could silently execute `cd ` (defaulting to `$HOME`) and run docker commands in the wrong
directory.

**Action:** Change to `set -euo pipefail` at the top of every shell script.

### S-7. CI/CD pipeline is disabled

**File:** `.github/workflows/deploy.yml.disabled`
The only workflow file has been disabled. No automated tests run on PRs, no dependency or container scans, no preventing
a regression from reaching `main`.

**Action:** Re-enable. At minimum the workflow should run `composer test`, `npm run lint && npm run build`, and
`flutter analyze && flutter test` on every PR. Add `aquasecurity/trivy-action` for image scanning before deploy.

### Q-1. Web frontend has zero tests

**File:** `web/` — no `jest.config`, `vitest.config`, `__tests__/`, or `*.test.tsx`.
The admin panel performs CRUD across every backend resource and handles auth. A regression in `AutoForm` or
`api/client.ts` would ship silently.

**Action:** Add Vitest + React Testing Library. Start with: API client error paths, `AutoForm` field rendering by type,
auth flow happy path.

### Q-2. Mobile app has zero tests

**File:** `mobile/test/widget_test.dart` — only the default Flutter counter stub.
**Action:** Add unit tests for each use case under `features/auth/domain/usecases/`, and bloc tests for `AuthBloc`. Aim
for a baseline of 30% coverage on the `domain/` layer.

### Q-3. Mobile dependencies are not version-pinned

**File:** `mobile/pubspec.yaml:7-30`
Most entries are bare (`dio:`, `flutter_bloc:`, `dartz:` etc.) — `pub get` resolves to whatever's latest. A new patch
release of any of these can break a build on a fresh checkout.

**Action:** Add caret constraints (`^x.y.z`) to every dependency. Commit `pubspec.lock`.

---

## P2 — Medium Priority (Next Sprint)

### A-2. Inconsistent auth approach: policy-based vs permission-based

`PromotionController` uses Laravel Policies. `Admin/V1/ResourceController.php:205` uses `$u->can("{$perm}.view")`
directly. Both are valid, but new contributors won't know which to use where.
**Action:** Add a short `backend/ARCHITECTURE.md` documenting: policies for owned-resource checks (the user owns this
thing), Spatie permissions for capability checks (this user has admin rights). Don't refactor existing code — just
document the split.

### A-3. AuthService returns untyped arrays

**File:** `backend/app/Services/AuthService.php:35` and others.
Returns `['token' => ..., 'user' => ..., 'profile_complete' => ...]`. There's already an `app/DTOs/` directory.
**Action:** Define `App\DTOs\AuthTokenResponse` (readonly class) and return that. Resources can hydrate from it.

### P-3. Composite index gap on promotions queries

**File:** `backend/database/migrations/2026_05_10_000001_create_promotions_table.php`
The "nearby active promotions" query in `PromotionService::getNearby()` filters on `is_active`, `valid_from`,
`valid_to`, plus a spatial join. Existing `[venue_id, is_active]` index doesn't help.
**Action:** Add `$table->index(['is_active', 'valid_from', 'valid_to'])`. Confirm with `EXPLAIN ANALYZE` against a
realistic dataset before/after.

### P-4. OTP rate-limit is per-phone, not per-IP

**File:** `backend/app/Services/OTPService.php:30`
An attacker can burn through OTPs by cycling phone numbers from one IP.
**Action:** Add Laravel's built-in `throttle:5,1` middleware to the `request-otp` route (5 per minute per IP), keeping
the per-phone limit as a second layer.

### Q-4. Magic numbers in OTP and Pin services

**Files:** `backend/app/Services/OTPService.php:66,110` (TTLs 3600 and 900) and
`backend/app/Services/PinService.php:16` (cache TTL 30).
**Action:** Move to `config/otp.php` and `config/services.php` respectively. Reference via
`config('otp.lock_duration')`.

### Q-5. Web: dead `@tanstack/react-table` dependency

**File:** `web/package.json`
Listed but unused (custom `DataTable` component is in place).
**Action:** `npm uninstall @tanstack/react-table`. Also remove `gen:api` script if no `openapi-typescript` artifact will
be committed.

### Q-6. Web: scanner table accessibility

**File:** `web/src/components/data-table.tsx:83`
Uses `role="link"` on `<tr>` — screen readers will announce it as a link but keyboard tab-order and `Enter` semantics
are inconsistent. WCAG anti-pattern.
**Action:** Either nest a real `<a>` inside the first cell (semantic) or use `<button>` with `onClick` and visible focus
ring.

### I-1. Container image tags not pinned

**Files:** `docker-compose.yml:15,71,93`, `docker-compose.local.yml:52,73`
`caddy:2-alpine`, `redis:7-alpine` will float to the latest minor. A future Caddy 2.x release could change directive
syntax.
**Action:** Pin to exact patch (`caddy:2.8.1-alpine`, `redis:7.4.1-alpine`) and dependabot to bump them.

### I-2. No resource limits on containers

**Files:** `docker-compose.yml`, `docker-compose.local.yml`
A runaway queue worker can OOM-kill the whole host.
**Action:** Add `deploy.resources.limits.memory` and `.cpus` on app, queue, redis, db.

### I-3. No log rotation

**Files:** `docker-compose.yml` (root or per-service)
Docker's default `json-file` driver grows unbounded.
**Action:** Add `logging` block per service:

```yaml
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"
```

### I-4. No rate limiting at Caddy

**File:** `caddy/Caddyfile`
Brute force against `/api/v1/auth/*` is wide open at the edge.
**Action:** Add the `rate_limit` directive (requires Caddy 2.x with the `caddy-ratelimit` module, or fall back to
Laravel's `throttle:` middleware on the auth route group).

### I-5. No backup strategy for PostGIS

**File:** `docker-compose.yml:141`
`db-data:/var/lib/postgresql/data` named volume, no automated dump.
*Note:* If the actual prod DB is the Aiven instance referenced in `.env.docker`, Aiven handles backups — confirm. The
local compose still needs a strategy if it's used for staging.

---

## P3 — Low Priority (Backlog)

| #    | Area    | File / Location                                                                | Issue                                                                                                             |
|------|---------|--------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------|
| Q-7  | Backend | `app/Services/PromotionService.php:124`, `app/Policies/PromotionPolicy.php:47` | Duplicated `relationLoaded('venue') / loadMissing()` pattern. Extract to a trait.                                 |
| Q-8  | Backend | `app/Http/Controllers/Admin/V1/ResourceController.php:120`                     | Audit logs don't include request IP / user-agent. Use structured `Log::channel('audit')` with context middleware. |
| Q-9  | Backend | `tests/Feature/Authorization/`                                                 | Add negative test: "non-owner cannot view another owner's promotion" (proves S-5 fix).                            |
| Q-10 | Web     | `web/src/lib/api/client.ts:71`                                                 | `any` return on mutation success. Define a `MutationResponse<T>` wrapper.                                         |
| Q-11 | Web     | `web/` (root)                                                                  | No `.prettierrc`. Add one to lock formatting across the team.                                                     |
| Q-12 | Web     | `web/src/app/admin/scanner/page.tsx`                                           | `BarcodeDetector` is Chrome/Android only — no graceful UX for Safari/Firefox users. Show a "use Chrome" hint.     |
| Q-13 | Mobile  | `mobile/lib/core/app_bloc_observer.dart`                                       | `print()` calls in production code — wrap with `if (kDebugMode)` or use the existing logger.                      |
| Q-14 | Mobile  | `mobile/lib/features/.../*.dart`                                               | Almost no `Semantics` widgets. Add for buttons, form fields, status messages.                                     |
| Q-15 | Mobile  | `mobile/analysis_options.yaml`                                                 | Only default `flutter_lints`. Add `prefer_const_constructors`, `avoid_print`, `require_trailing_commas` etc.      |
| I-6  | Infra   | `caddy/Caddyfile:23`                                                           | Personal email in committed config. Move to env var or use a role address.                                        |
| I-7  | Infra   | `docker-compose.yml:21`                                                        | HTTP/3 UDP exposed on 443/udp; `deploy-remote.sh:200` only opens TCP in UFW. Add `ufw allow 443/udp`.             |
| I-8  | Infra   | `deploy.sh:93`, `deploy-remote.sh:302`                                         | No `docker compose config` validation before build. Add as a guard.                                               |
| I-9  | Infra   | `deploy.sh`, `deploy-remote.sh`                                                | No rollback procedure documented or scripted. At minimum, retain the previous image tag for quick revert.         |

---

## Verified Findings vs. Investigation Notes

Two findings raised during the audit pass were dropped after verification:

- **"Flutter logger is uninitialized"** — false. `Logger()` is constructed at
  `mobile/lib/core/network/interceptor.dart:163`. The agent missed the initialization.
- **"`PromotionPolicy::view` returns true unconditionally is a bug"** — by design. Promotions are publicly viewable for
  the discovery flow; the policy is correct. (But the Business `show()` issue, S-5, is real because it returns *all
  fields* not just the public discovery resource.)

---

## Suggested Execution Order

If you approve everything, here's the sensible order to apply changes:

1. **Today, manual:** Rotate all four exposed secrets (S-1). This is the only item that *must* happen outside this
   session.
2. **Session 1 (this codebase):** All P0 code-level fixes — ~~S-2 build-time keys~~ (deferred), S-3 secure storage, S-4
   User `$hidden`, S-5 missing authorize call.
3. **Session 2:** P1 backend — A-1 policies, P-1 index migration, P-2 expiry job, S-6 bash hardening, S-7 CI re-enable.
4. **Session 3:** P1 frontends — Q-1 web tests baseline, Q-2 mobile tests baseline, Q-3 pubspec pinning.
5. **Session 4:** P2 cleanups in order of the table.
6. **Backlog:** P3 items as time permits.

---

## Ready to Execute?

Reply with which items to apply. Options:

- **"all P0"** — I'll fix S-2 through S-5 in this session. (S-1 requires you to rotate secrets manually.)
- **"all P0 and P1"** — adds the rest of the high-priority block. ~1–2 hours of code changes.
- **Pick specific IDs** — e.g. "S-4, S-5, P-1, A-1, Q-1" and I'll do exactly those.
- **"plan only, no changes"** — I'll stop here.
