#!/bin/bash
set -e

# Enable logging to stdout/stderr for Azure log stream visibility
exec 1> >(tee -a /dev/stdout)
exec 2> >(tee -a /dev/stderr)

echo "🚀 Starting Azure Web App startup script..."
echo "Timestamp: $(date)"
echo "Current directory: $(pwd)"
echo "User: $(whoami)"

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

echo "📁 Creating directories and setting up Laravel..."

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

echo "✅ Directory setup completed"

# Change to the application directory
cd /home/site/wwwroot

# Run Laravel optimization commands
echo "🔧 Running Laravel artisan commands..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Laravel optimization completed"

# Set up Nginx configuration
echo "🌐 Configuring Nginx..."
cp /home/site/wwwroot/azure/nginx_laravel.conf /etc/nginx/sites-enabled/default

# Ensure PHP-FPM is running
echo "🐘 Starting PHP-FPM..."
service php8.4-fpm start || service php8.3-fpm start || service php8.2-fpm start || service php8.1-fpm start || echo "⚠️  PHP-FPM service not found, continuing..."

# Start Nginx
echo "🌐 Starting Nginx..."
service nginx start

# Check if services are running
echo "🔍 Checking service status..."
if pgrep -f "nginx" > /dev/null; then
    echo "✅ Nginx is running (PID: $(pgrep -f nginx))"
else
    echo "❌ Nginx failed to start"
    echo "Nginx error log:"
    tail -20 /var/log/nginx/error.log || echo "No nginx error log found"
    exit 1
fi

if pgrep -f "php-fpm" > /dev/null; then
    echo "✅ PHP-FPM is running (PID: $(pgrep -f php-fpm))"
else
    echo "❌ PHP-FPM failed to start"
    echo "PHP-FPM error log:"
    tail -20 /var/log/php8.4-fpm.log 2>/dev/null || tail -20 /var/log/php8.3-fpm.log 2>/dev/null || echo "No PHP-FPM error log found"
    exit 1
fi

# Test if Laravel is accessible
echo "🧪 Testing Laravel application..."
sleep 5  # Give services time to fully start
if curl -f http://localhost:8080 > /dev/null 2>&1; then
    echo "✅ Laravel application is responding"
else
    echo "❌ Laravel application is not responding"
    echo "Nginx error log:"
    tail -20 /var/log/nginx/error.log || echo "No nginx error log found"
    echo "PHP-FPM error log:"
    tail -20 /var/log/php8.4-fpm.log 2>/dev/null || tail -20 /var/log/php8.3-fpm.log 2>/dev/null || echo "No PHP-FPM error log found"
    exit 1
fi

echo "🎉 Startup script completed successfully!"
echo "📊 Service Status Summary:"
echo "   - Nginx: ✅ Running on port 8080"
echo "   - PHP-FPM: ✅ Running"
echo "   - Laravel: ✅ Accessible"
echo "   - Timestamp: $(date)"
echo ""
echo "📝 To view logs in real-time:"
echo "   - Azure Portal: Web App → Monitoring → Log stream"
echo "   - Azure CLI: az webapp log tail --name $APP --resource-group $RG"
echo "   - Kudu: Web App → Development Tools → Kudu → Debug Console"
