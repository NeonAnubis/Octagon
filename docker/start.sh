#!/bin/sh
set -e

cd /var/www/html

# Generate APP_KEY if not set in base64 format
if [ -z "$APP_KEY" ] || echo "$APP_KEY" | grep -qv "^base64:"; then
    echo "==> Generating application key..."
    php artisan key:generate --force
fi

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
