#!/usr/bin/env bash
set -Eeuo pipefail

echo "[startup] ===== Laravel Startup Script BEGIN $(date -Is) ====="

APP_ROOT="/home/site/wwwroot"
cd "$APP_ROOT"

if [ -f "$APP_ROOT/azure/nginx_laravel.conf" ]; then
  echo "[startup] Installing nginx site config..."
  cp "$APP_ROOT/azure/nginx_laravel.conf" /etc/nginx/sites-enabled/default || true
  cp "$APP_ROOT/azure/nginx_laravel.conf" /etc/nginx/sites-available/default || true
fi

echo "[startup] Ensuring storage & cache directories exist..."
mkdir -p /home/storage/{app/public,app/private,framework/{sessions,views,cache,cache/data,cache/compiled},logs}
mkdir -p /home/cache

echo "[startup] Creating Laravel storage symlinks..."
mkdir -p "$APP_ROOT/storage/app/public"
mkdir -p "$APP_ROOT/storage/framework/cache/data"
mkdir -p "$APP_ROOT/storage/framework/sessions"
mkdir -p "$APP_ROOT/storage/framework/views"
mkdir -p "$APP_ROOT/storage/logs"

echo "[startup] Setting storage permissions..."
chmod -R 755 "$APP_ROOT/storage"
chmod -R 755 "$APP_ROOT/bootstrap/cache"

echo "[startup] Checking Laravel version..."
php artisan --version || true

echo "[startup] Running database migrations..."
php artisan migrate --force || true

echo "[startup] Running: php artisan storage:link"
php artisan storage:link || true

echo "[startup] Clearing caches (config/route/view) if present..."
php artisan cache:clear   || true
php artisan config:clear  || true
php artisan route:clear   || true
php artisan view:clear    || true

echo "[startup] Optimizing application..."
php artisan optimize || true

echo "[startup] Rebuilding caches..."
php artisan config:cache || true
php artisan route:cache  || true
php artisan view:cache   || true

echo "[startup] Restarting queues (if any)..."
php artisan queue:restart || true

echo "[startup] PHP version info:"
php -v | head -n 1 || true

echo "[startup] ===== Laravel Startup Script END $(date -Is) ====="

echo "[startup] Handover to platform starter..."
echo "[startup] ===== END $(date -Is) ====="

exit 0
