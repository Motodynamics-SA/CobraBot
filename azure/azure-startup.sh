#!/bin/bash
set -e

# Set environment variables for Laravel (only for the startup script)
export APP_STORAGE_PATH=/home/storage
export APP_CONFIG_CACHE=/home/cache/config.php
export APP_ROUTES_CACHE=/home/cache/routes.php
export APP_VIEWS_CACHE=/home/cache/views.php
export APP_PACKAGES_CACHE=/home/cache/packages.php
export APP_SERVICES_CACHE=/home/cache/services.php
export APP_EVENTS_CACHE=/home/cache/events.php
export APP_BASE_PATH=/home/site/wwwroot
export APP_BOOTSTRAP_PATH=/home/bootstrap

# Create cache directory
mkdir -p /home/cache

# Create storage directories
mkdir -p /home/storage/app/public
mkdir -p /home/storage/app/private
mkdir -p /home/storage/framework/sessions
mkdir -p /home/storage/framework/views
mkdir -p /home/storage/framework/cache
mkdir -p /home/storage/framework/cache/data
mkdir -p /home/storage/framework/cache/compiled
mkdir -p /home/storage/logs

cp -r /home/site/wwwroot/bootstrap/. /home/bootstrap/
cp -r /home/site/wwwroot/storage/. /home/storage/

# Set correct ownership and permissions for the writable directories
chown -R www-data:www-data /home/storage /home/cache
chmod -R 775 /home/storage /home/cache

# Change to the application directory
cd /home/site/wwwroot

# Run Laravel optimization commands
echo "Running artisan commands..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set up Nginx and restart the service
echo "Configuring and restarting Nginx..."
cp /home/site/wwwroot/azure/nginx_laravel.conf /etc/nginx/sites-enabled/default
service nginx restart

echo "✅ Startup script completed successfully."
