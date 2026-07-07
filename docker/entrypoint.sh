#!/bin/bash
set -e

php artisan storage:link --force 2>/dev/null || true

if [ ! -f /var/www/storage/framework/.db_initialized ]; then
    echo "→ Database fresh — migrating + seeding..."
    php artisan migrate --force
    php artisan db:seed --force
    touch /var/www/storage/framework/.db_initialized
    echo "✓ Initialization complete"
else
    echo "→ Database already initialized — running pending migrations..."
    php artisan migrate --force
fi

exec "$@"
