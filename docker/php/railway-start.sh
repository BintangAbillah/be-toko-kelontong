#!/bin/sh
set -e

export PORT="${PORT:-8080}"

envsubst '$PORT' < docker/nginx/railway.conf.template > /etc/nginx/sites-enabled/default

php artisan config:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true
php artisan config:cache
php artisan view:cache

migrate_with_retry() {
    attempt=1
    max_attempts="${MIGRATION_MAX_ATTEMPTS:-12}"
    sleep_seconds="${MIGRATION_RETRY_SECONDS:-5}"

    while [ "$attempt" -le "$max_attempts" ]; do
        echo "Running database migrations, attempt ${attempt}/${max_attempts}..."

        if php artisan migrate --force; then
            echo "Database migrations completed."
            return 0
        fi

        attempt=$((attempt + 1))
        echo "Migration failed. Retrying in ${sleep_seconds}s..."
        sleep "$sleep_seconds"
    done

    echo "Database migrations failed after ${max_attempts} attempts." >&2
    return 1
}

php-fpm -D

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    migrate_with_retry &
fi

exec nginx -g "daemon off;"
