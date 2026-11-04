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

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

php artisan optimize

exec docker-php-entrypoint "$@"
