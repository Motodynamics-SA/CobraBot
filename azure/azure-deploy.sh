#!/bin/bash

RESOURCE_GROUP="rg-price-updater"
APP_NAME="price-updater-app"
ZIP_FILE="laravel-app.zip"

echo "Preparing Laravel for production..."

# go to the project root
cd ..

# Back up your local .env if it exists
if [ -f .env ]; then
  echo "Backing up local .env to .env.local"
  mv .env .env.local
fi

# Use production env
echo "Copying .env.production to .env (for build only)"
cp .env.production .env

# Install dependencies
composer install --no-dev --optimize-autoloader

# Build frontend assets
echo "Building frontend assets..."
npm install
npm run prod

# Download Azure MySQL CA Cert
echo "Downloading Azure MySQL CA Certificate..."
wget https://dl.cacerts.digicert.com/DigiCertGlobalRootCA.crt.pem -O AzureMySQLTrustedCACerts.pem

# Make the startup script executable
chmod +x azure/azure-startup.sh

# Create the zip file
# Create zip including the temporary .env
echo "Creating deployment package..."
zip -r $ZIP_FILE . \
  -x ".env.production" \
  -x ".env.testing" \
  -x ".env.example" \
  -x ".env.ddev" \
  -x ".env.native" \
  -x ".editorconfig" \
  -x "deploy-laravel-to-azure.sh" \
  -x "set-laravel-permissions.sh" \
  -x "azure-deploy.sh" \
  -x ".env.local" \
  -x ".env.native.example" \
  -x ".env.ddev.example" \
  -x "*.git*" \
  -x "node_modules/*" \
  -x "tests/*" \
  -x ".ddev/*" \
  -x "*.md" \
  -x ".idea/*" \
  -x ".vscode/*" \
  -x ".phpunit.*" \
  -x ".github/*" \
  -x ".cursor/*" \
  -x ".cursor*" \
  -x "coverage/*" \
  -x "tools/*" \
  -x ".eslint*" \
  -x "eslint*" \
  -x "phpunit*" \
  -x "config.ddev*" \
  -x "jest*" \
  -x "phpstan*" \
  -x "docker-compose*" \
  -x "tailwind*" \
  -x "vite*" \
  -x "webpack*" \
  -x "vite.config.*" \
  -x "rector*" \
  -x "php-cs-fixer*" \
  -x "package-lock.json" \
  -x "package.json" \
  -x ".nvmrc" \
  -x "pint.json" \
  -x "prettier*" \
  -x ".prettier*" \
  -x "postcss*" \
  -x "tsconfig*"

# Restore your original .env (if it existed)
if [ -f .env.local ]; then
  echo "Restoring local .env"
  mv .env.local .env
else
  echo "Removing temporary .env"
  rm .env
fi

# Remove the downloaded CA certificate
rm AzureMySQLTrustedCACerts.pem

# Deploy to Azure
echo "Deploying to Azure Web App..."
az webapp deploy \
  --resource-group "$RESOURCE_GROUP" \
  --name "$APP_NAME" \
  --src-path "$ZIP_FILE" \
  --type zip

# Set environment variables
echo "Setting Azure App Service environment variables..."
az webapp config appsettings set \
  --resource-group "$RESOURCE_GROUP" \
  --name "$APP_NAME" \
  --settings APP_STORAGE_PATH=/home/storage APP_CONFIG_CACHE=/home/cache/config.php APP_ROUTES_CACHE=/home/cache/routes.php APP_VIEWS_CACHE=/home/cache/views.php APP_PACKAGES_CACHE=/home/cache/packages.php APP_SERVICES_CACHE=/home/cache/services.php APP_EVENTS_CACHE=/home/cache/events.php APP_BASE_PATH=/home/site/wwwroot APP_BOOTSTRAP_PATH=/home/bootstrap

# Set Azure App Service startup command for Nginx
echo "Setting the Azure App Service startup command..."
az webapp config set \
  --resource-group "$RESOURCE_GROUP" \
  --name "$APP_NAME" \
  --startup-file "/home/site/wwwroot/azure/azure-startup.sh"

if [ $? -eq 0 ]; then
  echo "Azure App Service startup command set successfully."
else
  echo "Failed to set Azure App Service startup command."
fi

rm -f $ZIP_FILE


echo "✅ Deployment complete."
