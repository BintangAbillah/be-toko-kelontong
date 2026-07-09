#!/bin/sh
set -e

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true

exec "$@"
