#!/bin/bash
set -e

echo "============================================"
echo " LiveMapEvents — Container Starting"
echo "============================================"

cd /var/www/html

# ── Step 1: Create .env from environment variables ──
# Docker Compose passes env vars from .env.docker into the container.
# Laravel reads config from .env, so we create it from those env vars.
# We ALWAYS recreate it to ensure it reflects the latest .env.docker values.
echo "[entrypoint] Creating .env from environment variables..."
env | grep -E '^(APP_|DB_|REDIS_|CACHE_|QUEUE_|SESSION_|LOG_|MAIL_|BCRYPT_|GOOGLE_|ULTRAMSG_|FILESYSTEM_|BROADCAST_)' \
    | sort > .env
# Ensure .env exists even if no matching vars
touch .env

# ── Step 2: Generate APP_KEY if not set ──
# APP_KEY is left empty in .env.docker on purpose.
# key:generate writes it into .env, and it persists across restarts
# because we only regenerate if it's missing.
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "[entrypoint] Generating application key..."
    php artisan key:generate --force
fi

# ── Step 3: Cache config, routes, views for production ──
# This must run AFTER key:generate so the cached config includes the key.
echo "[entrypoint] Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── Step 4: Run database migrations ──
# The db container should be healthy by now (docker-compose healthcheck),
# but we retry once in case of brief startup delays.
echo "[entrypoint] Running database migrations..."
php artisan migrate --force || {
    echo "[entrypoint] WARNING: Migrations failed. Retrying in 5 seconds..."
    sleep 5
    php artisan migrate --force || {
        echo "[entrypoint] ERROR: Migrations failed twice. Check DB connection."
        echo "[entrypoint] Debug: DB_HOST=$DB_HOST DB_DATABASE=$DB_DATABASE DB_USERNAME=$DB_USERNAME"
    }
}

# ── Step 5: Generate Swagger API docs ──
echo "[entrypoint] Generating Swagger API docs..."
php artisan l5-swagger:generate || echo "[entrypoint] WARNING: Swagger generation failed (non-fatal)"

# ── Step 6: Storage and permissions ──
php artisan storage:link 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache


echo "[entrypoint] Starting services..."
exec "$@"
