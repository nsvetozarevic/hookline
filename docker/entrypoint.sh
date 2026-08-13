#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    if [ -f .env ] && grep -q '^APP_KEY=$' .env 2>/dev/null; then
        php artisan key:generate --force --no-interaction || true
    fi
fi

exec docker-php-entrypoint "$@"
