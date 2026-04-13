# =============================================================================
#  API Checklist - Production Dockerfile
#  Multi-stage: Node (build assets) → PHP 8.2-FPM + Nginx + Supervisor
# =============================================================================

# ── Stage 1: Build frontend assets ──────────────────────────────────────────
FROM node:20-alpine AS assets-build

WORKDIR /build

COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts

COPY vite.config.js ./
COPY resources/ ./resources/

RUN npm run build


# ── Stage 2: Composer dependencies ──────────────────────────────────────────
FROM php:8.2-cli-bookworm AS composer-build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /build

COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative


# ── Stage 3: Production image ───────────────────────────────────────────────
FROM php:8.2-fpm-bookworm AS production

LABEL maintainer="API Checklist"
LABEL description="API Checklist - Laravel Application"

ARG WWWUSER=1000
ARG WWWGROUP=1000

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=America/Sao_Paulo

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    cron \
    ffmpeg \
    ghostscript \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    libmagickwand-dev \
    libsodium-dev \
    libreadline-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        xml \
        mbstring \
        curl \
        zip \
        bcmath \
        gd \
        intl \
        fileinfo \
        pcntl \
        sodium \
        opcache \
    && pecl install redis imagick \
    && docker-php-ext-enable redis imagick \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# ── PHP production config ───────────────────────────────────────────────────
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

# ── Application ─────────────────────────────────────────────────────────────
WORKDIR /var/www/html

COPY --from=composer-build /build/vendor ./vendor
COPY . .
COPY --from=assets-build /build/public/build ./public/build

RUN rm -rf \
    .git \
    .env \
    .env.example \
    node_modules \
    tests \
    docker \
    install.sh \
    *.md \
    storage/logs/*.log

RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

RUN groupadd -g ${WWWGROUP} appgroup || true \
    && useradd -u ${WWWUSER} -g ${WWWGROUP} -s /bin/bash -m appuser || true \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/app.conf", "-n"]
