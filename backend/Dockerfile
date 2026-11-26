# ============================================================================
# Stage 1: Base Runtime (FrankenPHP 8.4 Alpine + Extensions + OPcache + Caddy)
# ============================================================================
FROM dunglas/frankenphp:1-php8.4-alpine AS base

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    redis \
    bcmath \
    intl \
    pcntl \
    opcache \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Co-located PHP & FrankenPHP Caddy runtime configuration (zero stray root docker/ folder)
RUN printf '%s\n' \
    'memory_limit = 512M' \
    'upload_max_filesize = 25M' \
    'post_max_size = 30M' \
    'max_execution_time = 60' \
    'opcache.enable = 1' \
    'opcache.enable_cli = 1' \
    'opcache.memory_consumption = 256' \
    'opcache.interned_strings_buffer = 16' \
    'opcache.max_accelerated_files = 20000' \
    'expose_php = Off' \
    > /usr/local/etc/php/conf.d/99-cleancommerce.ini && \
    printf '%s\n' \
    '{' \
    '    frankenphp' \
    '    order php_server before file_server' \
    '}' \
    ':80 {' \
    '    root * /app/public' \
    '    encode zstd br gzip' \
    '    php_server' \
    '}' \
    > /etc/caddy/Caddyfile

WORKDIR /app

# ============================================================================
# Stage 2: Development Target (Includes PHPUnit & Laravel Pint for Local Dev)
# ============================================================================
FROM base AS dev

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize && \
    mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

EXPOSE 80

# ============================================================================
# Stage 3: Lean Production Target (@anonystick Multi-Stage + Non-Root + Healthcheck)
# ============================================================================
FROM base AS prod

ENV APP_ENV=production \
    APP_DEBUG=false

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --classmap-authoritative --no-dev && \
    rm -rf tests docs /usr/bin/composer && \
    addgroup -g 1000 appgroup && \
    adduser -u 1000 -G appgroup -s /bin/sh -D appuser && \
    mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache && \
    chown -R appuser:appgroup /app /data /config && \
    chmod -R 775 storage bootstrap/cache

USER appuser

HEALTHCHECK --interval=15s --timeout=3s --start-period=10s --retries=3 \
    CMD wget --no-verbose --spider http://127.0.0.1:80/api/v1/health || exit 1

EXPOSE 80
