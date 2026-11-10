#!/bin/sh
set -eu
if [ "${SKIP_BOOTSTRAP:-0}" != "1" ]; then
    rm -f /tmp/app-ready
    composer install --no-interaction --prefer-dist --no-progress
    mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
    php artisan migrate --force --no-interaction
    if [ "${APP_ENV:-local}" = "local" ]; then
        php artisan db:seed --force --no-interaction
    fi
    chown -R www-data:www-data storage bootstrap/cache
    touch /tmp/app-ready
fi
exec docker-php-entrypoint "$@"
