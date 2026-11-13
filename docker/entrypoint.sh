#!/usr/bin/env bash
set -e

cd /var/www/html

php artisan storage:link || true

if [ -n "${DB_HOST:-}" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan scheduler Laravel (evaluasi hangus tiap jam) di background
(
    while true; do
        php artisan schedule:run --no-interaction >/dev/null 2>&1 || true
        sleep 60
    done
) &

PORT_LISTEN="${PORT:-8080}"
exec php artisan serve --host=0.0.0.0 --port="${PORT_LISTEN}"
