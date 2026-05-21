# LiveEventsMap

A location-based live events discovery platform. Users explore a real-time map to find nearby events and venues, check
in to earn loyalty points, share short video stories, and receive proximity-triggered promotions.

## What It Does

- **Map-first discovery** — Events and venues appear as pins on an interactive map based on the user's location
- **Check-ins & loyalty** — Users check in at venues/events and accumulate points per venue; loyalty transactions are
  recorded per check-in
- **Vibe stories** — Users post short-lived video stories tied to a venue or event (similar to Instagram Stories)
- **Proximity promotions** — Venues create geo-fenced promotions with a radius in meters; users nearby see relevant
  offers
- **Venue verification** — Venue owners submit registration documents; admins review and approve before the venue goes
  live
- **RBAC** — Role-based access control via Spatie Laravel Permission (users, venue owners, admins)
- **Google OAuth + phone auth** — Users sign in via Google or phone number

## Stack

| Layer          | Technology                                 |
|----------------|--------------------------------------------|
| Backend        | Laravel 12 (PHP 8.2+)                      |
| Auth           | Laravel Sanctum + Spatie Permission        |
| Google OAuth   | `google/apiclient`                         |
| Database       | PostgreSQL (PostGIS for `geometry` fields) |
| Cache / Queue  | Redis (`predis/predis`)                    |
| Asset Pipeline | Vite 7 + Tailwind CSS v4                   |
| Web Frontend   | Planned (not yet implemented)              |
| Mobile         | Flutter (not yet implemented)              |

> **Note:** The default `.env` uses SQLite, but the schema uses PostGIS `geometry` types for user/venue/pin locations.
> PostgreSQL + PostGIS is required for production.

## Monorepo Structure

```
LiveMapEvents/
├── backend/      # Laravel 12 API + Blade (primary active codebase)
├── web/          # Web frontend (placeholder)
├── mobile/       # Flutter app (placeholder)
└── docs/
    ├── LiveEventsMap_ERD.html   # Interactive database diagram
    └── Shu_fi_SRS.pdf           # Software Requirements Specification
```

## Getting Started

All commands run from `backend/`.

### Prerequisites

- PHP 8.2+
- Composer
- Node.js + npm
- PostgreSQL with PostGIS extension (for geometry fields)
- Redis

### Setup

```bash
cd backend
composer setup
```

This installs PHP and Node dependencies, generates the app key, and runs migrations.

### Development

```bash
composer dev
```

Starts four processes concurrently: Laravel server, queue worker, log tail (Pail), and Vite dev server.

### Build

```bash
npm run build
```

### Testing

```bash
composer test                                      # All tests
php artisan test --filter TestName                 # Single test
```

### Code Style

```bash
./vendor/bin/pint    # PSR-12 formatter
```

## Mobile App (Flutter)

The Flutter app lives in `mobile/` and builds for Android, iOS, and Web. Most setup is the standard Flutter flow (
`flutter pub get`, `flutter run`, `flutter build apk --release`), with one per-developer step for Google Sign-In on
Android.

### Google Sign-In on Android — register your SHA-1

Each developer's machine has its own auto-generated `~/.android/debug.keystore` with a unique SHA-1 fingerprint, and
Google's Android OAuth client UI now allows only **one SHA-1 per client**. So every developer needs their **own**
Android OAuth client in Cloud Console, all sharing the same package name (`com.omar.mobile`). Existing clients are left
untouched — never overwrite another dev's SHA-1, or their builds stop signing in.

**1. Get your debug-keystore SHA-1:**

```bash
cd mobile/android
./gradlew signingReport
```

Look for `:app` → `Variant: debug` → the `SHA1:` line and copy that value.

**2. Create an Android OAuth client in Google Cloud Console:**

Open [APIs & Services → Credentials](https://console.cloud.google.com/apis/credentials), click *+ Create credentials →
OAuth client ID*, and fill in:

| Field                         | Value                                                             |
|-------------------------------|-------------------------------------------------------------------|
| Application type              | Android                                                           |
| Name                          | `LiveMapEventsAuthAndroid - <your name>` (anything descriptive)   |
| Package name                  | `com.omar.mobile` (matches `mobile/android/app/build.gradle.kts`) |
| SHA-1 certificate fingerprint | the value from step 1                                             |

Click *Create*. Wait ~30 seconds for Google's edge to propagate, then close and reopen the app on your phone.

> The Web OAuth client (used by the Laravel backend and the Flutter web build) is shared across the team and doesn't
> change. The Android client only gates which APKs are allowed to ask for sign-in — the ID token Google issues still has
> its `aud` set to the Web client ID, so backend verification works identically regardless of which developer's APK
> triggered it.

**3. (First time only) Create `mobile/.env`:**

```bash
cp mobile/.env.example mobile/.env
```

The Flutter app reads `GOOGLE_SERVER_CLIENT_ID` (the Web OAuth client ID) and `BASE_URL` from this file at runtime via
`flutter_dotenv` on Android/iOS. The Web build doesn't read it — those values are baked in at build time via
`--dart-define` in `mobile/Dockerfile.web`.

### Building an APK

```bash
cd mobile
flutter pub get
flutter build apk --release
```

The APK lands at `mobile/build/app/outputs/flutter-apk/app-release.apk`. Note that "release" here uses the debug signing
config (see `mobile/android/app/build.gradle.kts`) — for an actual Play Store release you'd configure a separate release
keystore and register its SHA-1 alongside the per-dev debug ones.

## Database Schema

The schema is organized into five domain groups. Open `docs/LiveEventsMap_ERD.html` in a browser for an interactive
view.

### Users & Auth

| Table                                                     | Purpose                                                                      |
|-----------------------------------------------------------|------------------------------------------------------------------------------|
| `users`                                                   | Core user profile — phone, Google ID, DOB, gender, avatar, geometry location |
| `roles` / `permissions` / `role_user` / `permission_role` | RBAC (Spatie)                                                                |
| `user_interests`                                          | Per-user event category preferences                                          |
| `device_tokens`                                           | Push notification tokens (iOS/Android)                                       |

### Venues & Events

| Table                    | Purpose                                                                |
|--------------------------|------------------------------------------------------------------------|
| `venues`                 | Owner, address, geometry location, active/verified flags               |
| `business_verifications` | Document submission and admin review for venue verification            |
| `events`                 | Title, category, start/end times, free flag, image — linked to a venue |
| `pins`                   | Map pin per venue or event with geometry location and promotion flag   |

### Engagement

| Table               | Purpose                                                     |
|---------------------|-------------------------------------------------------------|
| `checkins`          | User check-in to a venue/event with timestamp               |
| `saved_events`      | User bookmarks for events                                   |
| `user_interactions` | Impression/click/view tracking with duration per event      |
| `vibe_stories`      | Short video posts tied to venue/event; expire automatically |

### Loyalty & Promotions

| Table                          | Purpose                                                          |
|--------------------------------|------------------------------------------------------------------|
| `loyalty_accounts`             | Points balance per user per venue                                |
| `loyalty_transactions`         | Points delta per check-in with reason                            |
| `promotions`                   | Geo-fenced offer with `radius_meters`, discount, start/end times |
| `transactions`                 | Payment records linked to promotions                             |
| `voucher_batches` / `vouchers` | Admin-issued voucher codes with value and expiry                 |

### Admin

| Table           | Purpose                                                  |
|-----------------|----------------------------------------------------------|
| `activity_logs` | Audit log — actor, target type/id, action, JSON metadata |

## Design Resources

- **Figma**: https://www.figma.com/design/DivTMjMNiUzpTt8voDG1Jd/Event-Map
- **ERD**: `docs/LiveEventsMap_ERD.html`
- **SRS**: `docs/Shu_fi_SRS.pdf`