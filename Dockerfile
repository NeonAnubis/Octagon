# ── Stage 1: Build frontend assets ──────────────────────
FROM node:20-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources/ ./resources/
RUN npm run build

# ── Stage 2: Install PHP dependencies ──────────────────
FROM composer:2 AS composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
COPY . .
RUN composer dump-autoload --optimize

# ── Stage 3: Production image ──────────────────────────
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpq-dev \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    zip \
    curl

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    bcmath \
    intl \
    zip \
    opcache

# ── Optimize for Render Free Tier (512 MB RAM) ─────────

# OPcache — reduced memory for free tier
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=64" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# PHP — limited memory per process
RUN echo "memory_limit=128M" >> /usr/local/etc/php/conf.d/app.ini && \
    echo "upload_max_filesize=10M" >> /usr/local/etc/php/conf.d/app.ini && \
    echo "post_max_size=12M" >> /usr/local/etc/php/conf.d/app.ini

# PHP-FPM — minimal workers for 512 MB
RUN echo "[www]" > /usr/local/etc/php-fpm.d/zz-render.conf && \
    echo "pm = static" >> /usr/local/etc/php-fpm.d/zz-render.conf && \
    echo "pm.max_children = 3" >> /usr/local/etc/php-fpm.d/zz-render.conf && \
    echo "pm.max_requests = 500" >> /usr/local/etc/php-fpm.d/zz-render.conf

WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy PHP dependencies from composer stage
COPY --from=composer /app/vendor ./vendor

# Copy built frontend assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Create necessary directories
RUN mkdir -p storage/framework/{cache,sessions,views,testing} \
    storage/logs \
    bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Copy Nginx and Supervisor configs
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 10000

CMD ["/start.sh"]
