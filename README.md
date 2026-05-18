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