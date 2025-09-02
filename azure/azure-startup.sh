#!/usr/bin/env bash
set -Eeuo pipefail

echo "[startup] ===== Laravel Startup Script BEGIN $(date -Is) ====="

APP_ROOT="/home/site/wwwroot"
cd "$APP_ROOT"

# Apply PHP-FPM configuration for better reliability
if [ -f "$APP_ROOT/azure/ini/php-fpm-custom.ini" ]; then
  echo "[startup] Applying PHP-FPM configuration..."
  cp "$APP_ROOT/azure/ini/php-fpm-custom.ini" /usr/local/etc/php-fpm.d/zz-custom.conf || true
  # Restart PHP-FPM to apply new configuration
  service php8.4-fpm restart || service php-fpm restart || true
fi

if [ -f "$APP_ROOT/azure/nginx_laravel.conf" ]; then
  echo "[startup] Installing nginx site config..."
  cp "$APP_ROOT/azure/nginx_laravel.conf" /etc/nginx/sites-enabled/default || true
  cp "$APP_ROOT/azure/nginx_laravel.conf" /etc/nginx/sites-available/default || true
  echo "[startup] Reloading nginx..."
  nginx -t && service nginx reload
fi

echo "[startup] Ensuring storage & cache directories exist..."
mkdir -p /home/storage/{app/public,app/private,framework/{sessions,views,cache,cache/data,cache/compiled},logs}
mkdir -p /home/cache

echo "[startup] Checking Laravel version..."
php artisan --version || true

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
