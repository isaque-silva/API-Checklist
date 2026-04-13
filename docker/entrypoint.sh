#!/bin/bash
set -e

echo "============================================"
echo "  API Checklist - Container Starting"
echo "============================================"

cd /var/www/html

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "[entrypoint] Generating APP_KEY..."
    php artisan key:generate --force --no-interaction
fi

# Create storage link if not exists
if [ ! -L "public/storage" ]; then
    echo "[entrypoint] Creating storage link..."
    php artisan storage:link --no-interaction 2>/dev/null || true
fi

# Run migrations
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force --no-interaction 2>&1 || echo "[entrypoint] WARNING: Some migrations failed."
fi

# Run seeders (only on first deploy)
if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    echo "[entrypoint] Running seeders..."
    php artisan db:seed --force --no-interaction 2>&1 || echo "[entrypoint] WARNING: Some seeders failed."
fi

# Cache config/routes/views
echo "[entrypoint] Optimizing application..."
php artisan config:cache --no-interaction 2>/dev/null || true
php artisan route:cache --no-interaction 2>/dev/null || true
php artisan view:cache --no-interaction 2>/dev/null || true
php artisan optimize --no-interaction 2>/dev/null || true

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Setup cron for Laravel scheduler
echo "* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1" | crontab -

echo "============================================"
echo "  Application Ready - Port 80"
echo "============================================"

exec "$@"
