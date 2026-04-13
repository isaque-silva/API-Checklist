#!/bin/bash

echo "============================================"
echo "  API Checklist - Container Starting"
echo "============================================"

cd /var/www/html

# Create .env from environment variables if it doesn't exist
if [ ! -f ".env" ]; then
    echo "[entrypoint] Creating .env from environment variables..."
    env | grep -E '^(APP_|DB_|MAIL_|REDIS_|CACHE_|QUEUE_|SESSION_|LOG_|BROADCAST_|FILESYSTEM_|BCRYPT_|PHP_|VITE_|RUN_)' | sort > .env

    # Ensure essential defaults
    grep -q "^APP_ENV=" .env || echo "APP_ENV=production" >> .env
    grep -q "^APP_DEBUG=" .env || echo "APP_DEBUG=false" >> .env
    grep -q "^APP_KEY=" .env || echo "APP_KEY=" >> .env

    echo "[entrypoint] .env created with $(wc -l < .env) variables."
fi

# Generate APP_KEY if not set
CURRENT_KEY=$(grep "^APP_KEY=" .env | cut -d'=' -f2-)
if [ -z "$CURRENT_KEY" ] || [ "$CURRENT_KEY" = "base64:" ]; then
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
    if php artisan migrate --force --no-interaction 2>&1; then
        echo "[entrypoint] Migrations completed."
    else
        echo "[entrypoint] WARNING: Some migrations failed."
    fi
fi

# Run seeders (only on first deploy)
if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    echo "[entrypoint] Running seeders..."
    if php artisan db:seed --force --no-interaction 2>&1; then
        echo "[entrypoint] Seeders completed."
    else
        echo "[entrypoint] WARNING: Some seeders failed."
    fi
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
