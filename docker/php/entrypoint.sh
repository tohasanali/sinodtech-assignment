#!/bin/sh
set -e

if [ -d /var/www/html/storage ]; then
    chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
fi

exec docker-php-entrypoint "$@"
