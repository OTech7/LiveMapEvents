#!/bin/bash
set -e

echo "============================================"
echo " LiveMapEvents — Container Starting"
echo "============================================"

cd /var/www/html

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "[entrypoint] Generating application key..."
    php artisan key:generate --force
fi

# Cache config, routes, views for production
echo "[entrypoint] Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (safe with --force flag)
echo "[entrypoint] Running database migrations..."
php artisan migrate --force || {
    echo "[entrypoint] WARNING: Migrations failed. DB may not be ready yet."
    echo "[entrypoint] Will retry in 5 seconds..."
    sleep 5
    php artisan migrate --force || echo "[entrypoint] Migrations failed again. Check DB connection."
}

# Create storage link if not exists
php artisan storage:link 2>/dev/null || true

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache

echo "[entrypoint] Starting services..."
exec "$@"
