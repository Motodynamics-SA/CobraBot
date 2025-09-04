#!/usr/bin/env bash
set -Eeuo pipefail

echo "[startup] ===== Laravel Startup Script BEGIN $(date -Is) ====="

APP_ROOT="/home/site/wwwroot"
cd "$APP_ROOT"

# ---- Install nginx site (if present) ----
if [ -f "$APP_ROOT/azure/nginx_laravel.conf" ]; then
  echo "[startup] Installing nginx site config..."
  cp -f "$APP_ROOT/azure/nginx_laravel.conf" /etc/nginx/sites-enabled/default || true
  cp -f "$APP_ROOT/azure/nginx_laravel.conf" /etc/nginx/sites-available/default || true

  echo "[startup] Validating nginx config..."
  if nginx -t 2>&1; then
    echo "[startup] nginx config OK; reloading..."
    service nginx reload || true
  else
    echo "[startup] nginx config INVALID; restoring platform default"
    rm -f /etc/nginx/sites-enabled/default
    # Do NOT reload here; let the platform starter bring up the default config
  fi
fi

# ---- Ensure .user.ini is in place ----
if [ -f "$APP_ROOT/azure/.user.ini" ]; then
  echo "[startup] Installing .user.ini..."
  cp -f "$APP_ROOT/azure/.user.ini" "$APP_ROOT/.user.ini" || true
  chmod 644 "$APP_ROOT/.user.ini" || true
fi

# (Optional) quick warm ping 15s after boot so OPcache/views are primed
SITE_URL="${SITE_URL:-https://${WEBSITE_HOSTNAME:-}}"
if [ -n "$SITE_URL" ]; then
  nohup sh -c "sleep 15 && curl -fsS --max-time 5 $SITE_URL/warm >/dev/null 2>&1" >/dev/null 2>&1 &
fi

echo "[startup] Startup prep complete"

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
