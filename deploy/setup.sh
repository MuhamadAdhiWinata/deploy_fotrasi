#!/bin/bash
# ===========================================================
# FortasiMupa — Production Setup for Ubuntu 24 + aaPanel
# Run this ON the server after uploading the project files.
# ===========================================================
set -euo pipefail

echo "==> 1. Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache public/storage

echo "==> 2. Installing PHP dependencies (no dev)..."
composer install --no-dev --optimize-autoloader

echo "==> 3. Creating .env from example..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "   .env created — EDIT IT with your database credentials and domain!"
    echo "   Then re-run this script."
    exit 1
fi

echo "==> 4. Generating APP_KEY (if empty)..."
php artisan key:generate --force

echo "==> 5. Caching..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> 6. Running migrations..."
php artisan migrate --force

echo "==> 7. Seeding (first time only)..."
php artisan db:seed --force

echo "==> 8. Storage link..."
php artisan storage:link

echo "==> 9. Building frontend assets..."
npm install --ignore-scripts
npm run build

echo "==> 10. Restarting queue worker..."
# aaPanel → Supervisor → Add:
# Name: fortasi-queue
# Run User: www
# Run Dir: /www/wwwroot/fortasi
# Command: php artisan queue:work --sleep=3 --tries=3
# Processes: 1
# Start Sec: 10

echo ""
echo "========================================================"
echo "  DEPLOYMENT COMPLETE"
echo "========================================================"
echo ""
echo "  Next steps in aaPanel:"
echo ""
echo "  1. Website → Add site"
echo "     Domain: your-domain.com"
echo "     Path:   /www/wwwroot/fortasi"
echo ""
echo "  2. Replace nginx config with deploy/nginx.conf"
echo "     (edit domain & SSL paths)"
echo ""
echo "  3. SSL → Let's Encrypt"
echo ""
echo "  4. Supervisor → Add daemon:"
echo "     Name:    fortasi-queue"
echo "     Run User: www"
echo "     Run Dir: /www/wwwroot/fortasi"
echo "     Command: php artisan queue:work --sleep=3 --tries=3"
echo "     Processes: 1"
echo ""
echo "  5. Cron → Add (every minute):"
echo "     php /www/wwwroot/fortasi/artisan schedule:run"
echo ""
echo "  6. Verify at https://your-domain.com"
echo ""
