#!/bin/bash

echo "============================================"
echo "  API Checklist - Container Starting"
echo "============================================"

cd /var/www/html

# Create .env from environment variables if it doesn't exist
if [ ! -f ".env" ]; then
    echo "[entrypoint] Creating .env from environment variables..."
    : > .env

    write_env() {
        local var="$1"
        local val="${!var}"
        if [ -n "$val" ]; then
            echo "${var}=\"${val}\"" >> .env
        fi
    }

    write_env APP_NAME
    write_env APP_ENV
    write_env APP_KEY
    write_env APP_DEBUG
    write_env APP_URL
    write_env APP_TIMEZONE
    write_env APP_LOCALE
    write_env APP_FALLBACK_LOCALE
    write_env APP_FAKER_LOCALE
    write_env APP_MAINTENANCE_DRIVER

    write_env BCRYPT_ROUNDS
    write_env PHP_CLI_SERVER_WORKERS

    write_env LOG_CHANNEL
    write_env LOG_STACK
    write_env LOG_LEVEL
    write_env LOG_DEPRECATIONS_CHANNEL

    write_env DB_CONNECTION
    write_env DB_HOST
    write_env DB_PORT
    write_env DB_DATABASE
    write_env DB_USERNAME
    write_env DB_PASSWORD

    write_env SESSION_DRIVER
    write_env SESSION_LIFETIME
    write_env SESSION_ENCRYPT
    write_env SESSION_PATH
    write_env SESSION_DOMAIN

    write_env BROADCAST_CONNECTION
    write_env FILESYSTEM_DISK
    write_env QUEUE_CONNECTION

    write_env CACHE_STORE
    write_env CACHE_PREFIX

    write_env REDIS_CLIENT
    write_env REDIS_HOST
    write_env REDIS_PASSWORD
    write_env REDIS_PORT

    write_env MAIL_MAILER
    write_env MAIL_SCHEME
    write_env MAIL_HOST
    write_env MAIL_PORT
    write_env MAIL_USERNAME
    write_env MAIL_PASSWORD
    write_env MAIL_ENCRYPTION
    write_env MAIL_FROM_ADDRESS
    write_env MAIL_FROM_NAME
    write_env MAIL_TIMEOUT

    write_env VITE_APP_NAME
    write_env TINIFY_API_KEY

    # Ensure essential defaults if not set
    grep -q "^APP_ENV=" .env || echo 'APP_ENV="production"' >> .env
    grep -q "^APP_DEBUG=" .env || echo 'APP_DEBUG="false"' >> .env
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
