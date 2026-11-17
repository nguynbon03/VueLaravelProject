FROM dunglas/frankenphp:1-php8.4-alpine

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

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize && \
    mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

EXPOSE 80
