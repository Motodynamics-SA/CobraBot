#!/usr/bin/env bash
set -Eeuo pipefail

echo "[startup] Begin $(date -Is)"

APP_ROOT="/home/site/wwwroot"
cd "$APP_ROOT"

# 1) Ensure writable dirs on the persistent /home volume
mkdir -p /home/storage/{app/public,app/private,framework/{sessions,views,cache,cache/data,cache/compiled},logs} /home/cache
# Optional: point Laravel to these paths via env vars you already set (e.g., APP_STORAGE_PATH=/home/storage)

# 2) Laravel housekeeping (idempotent)
php artisan storage:link || true
php artisan optimize || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan queue:restart || true

# 3) (Optional) very fast health check log
php -v | head -n1 || true

echo "[startup] Done $(date -Is)"
