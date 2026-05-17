# ============================================================
# LiveMapEvents Backend — Multi-stage Docker Build
# PHP 8.3 + Nginx + PostgreSQL/PostGIS drivers
# ============================================================

# ---- Stage 1a: Composer dependencies (production — no dev) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# ---- Stage 1b: Composer dependencies (local/testing — includes dev) ----
FROM composer:2 AS vendor-dev
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs

# ---- Stage 2: Node / Vite build ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts 2>/dev/null || npm install
COPY vite.config.js ./
COPY resources/ resources/
RUN npm run build

# ---- Stage 3: Production image ----
FROM php:8.3-fpm-bookworm AS production

LABEL maintainer="LiveMapEvents Team"
LABEL description="LiveMapEvents Laravel API"

# --- System packages ---
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        mbstring \
        xml \
        bcmath \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get autoremove -y && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# --- PHP config ---
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-livemap.ini"

# --- Nginx config ---
COPY docker/nginx.conf /etc/nginx/sites-available/default

# --- Supervisor config ---
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# --- Application code ---
WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# --- Storage & cache directories ---
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache/data \
    storage/logs \
    storage/api-docs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# --- Entrypoint ---
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ---- Stage 4: Local / testing image ----
# Extends production but swaps in the dev vendor dir, adds composer,
# and uses the development php.ini so errors are visible in tests.
FROM production AS local

# Switch to development PHP config (shows errors, relaxed limits)
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Copy composer binary so `composer` is available inside the container
COPY --from=vendor-dev /usr/bin/composer /usr/local/bin/composer

# Replace the production vendor dir with the one that includes dev deps
COPY --from=vendor-dev /app/vendor ./vendor

# Re-own the newly copied vendor
RUN chown -R www-data:www-data vendor
