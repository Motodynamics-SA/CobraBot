#!/bin/bash
set -e

# Set variables
RESOURCE_GROUP="rg-price-updater"
LOCATION="France Central"  # change to West Europe if needed
APP_PLAN="plan-price-updater"
APP_NAME="price-updater-app"
MYSQL_NAME="price-updater-db"
MYSQL_ADMIN="laraveladmin"

echo "Enter MySQL Admin Password:"
read -s MYSQL_PASSWORD
MYSQL_SKU="Standard_B1ms" # fallback if needed: Standard_D2ds_v4

# Configure deployment user (global per subscription)
DEPLOY_USER="scifyadmin"
echo "Enter Deployment User Password:"
read -s DEPLOY_PASSWORD

# Check if resource group exists
echo "➡️  Creating resource group: $RESOURCE_GROUP"
az group create --name "$RESOURCE_GROUP" --location "$LOCATION"

# Create App Service Plan
echo "➡️  Creating App Service plan: $APP_PLAN"
az appservice plan create \
  --name "$APP_PLAN" \
  --resource-group "$RESOURCE_GROUP" \
  --sku B1 \
  --is-linux \
  --location "$LOCATION"

# Create Laravel Web App
echo "➡️  Creating Web App: $APP_NAME"
az webapp create \
  --resource-group "$RESOURCE_GROUP" \
  --plan "$APP_PLAN" \
  --name "$APP_NAME" \
  --runtime "PHP|8.2" \
  --deployment-local-git

# Wait for the Web App to be fully provisioned
echo "⏳ Waiting for web app '$APP_NAME' to become available..."
for i in {1..10}; do
  FOUND=$(az webapp show --name "$APP_NAME" --resource-group "$RESOURCE_GROUP" --query "name" --output tsv 2>/dev/null)
  if [ "$FOUND" == "$APP_NAME" ]; then
    echo "✅ Web app is ready."
    break
  fi
  echo "   ...still waiting..."
  sleep 5
done

echo "➡️  Setting Azure deployment user: $DEPLOY_USER"
az webapp deployment user set \
  --user-name "$DEPLOY_USER" \
  --password "$DEPLOY_PASSWORD"

# Now configure local Git
echo "ℹ️  Your local Git deployment URL:"
GIT_URL=$(az webapp deployment source config-local-git \
  --name "$APP_NAME" \
  --resource-group "$RESOURCE_GROUP" \
  --query url --output tsv)

echo "   $GIT_URL"

# Generate a new APP_KEY locally
APP_KEY_GENERATED=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")

# Create MySQL Flexible Server
echo "➡️  Creating MySQL Flexible Server: $MYSQL_NAME"
MYSQL_CREATED=0
az mysql flexible-server create \
  --resource-group "$RESOURCE_GROUP" \
  --name "$MYSQL_NAME" \
  --admin-user "$MYSQL_ADMIN" \
  --admin-password "$MYSQL_PASSWORD" \
  --sku-name "$MYSQL_SKU" \
  --storage-size 20 \
  --location "$LOCATION" \
  --version 8.0.21 \
  --public-access 0.0.0.0 && MYSQL_CREATED=1

# If creation failed, try fallback SKU
if [ $MYSQL_CREATED -eq 0 ]; then
  echo "⚠️  Primary MySQL SKU unavailable. Retrying with fallback SKU: Standard_D2ds_v4"
  az mysql flexible-server create \
    --resource-group "$RESOURCE_GROUP" \
    --name "$MYSQL_NAME" \
    --admin-user "$MYSQL_ADMIN" \
    --admin-password "$MYSQL_PASSWORD" \
    --sku-name Standard_D2ds_v4 \
    --storage-size 20 \
    --location "$LOCATION" \
    --public-access 0.0.0.0
fi

# Set Web App environment variables for Laravel
echo "➡️  Configuring Laravel environment variables"
az webapp config appsettings set \
  --name "$APP_NAME" \
  --resource-group "$RESOURCE_GROUP" \
  --settings \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY="$APP_KEY_GENERATED" \
    DB_CONNECTION=mysql \
    DB_HOST="$MYSQL_NAME.mysql.database.azure.com" \
    DB_PORT=3306 \
    DB_DATABASE=laravel \
    DB_USERNAME="$MYSQL_ADMIN@$MYSQL_NAME" \
    DB_PASSWORD="$MYSQL_PASSWORD"

# Run Laravel Migrations
echo "➡️  Running Laravel database migrations"
az webapp ssh cmd --name "$APP_NAME" --resource-group "$RESOURCE_GROUP" --command "php artisan migrate --force"

echo "✅ Deployment completed. Your app is at:"
echo "   https://$APP_NAME.azurewebsites.net"

echo "--- IMPORTANT: Build Process ---"
echo "For 'deployment-local-git', you need to ensure Composer dependencies and frontend assets are built."
echo "You can configure this in Azure App Service's Deployment Center (Kudu) or by adding a .deployment file."
echo "Example .deployment file for Laravel:"
echo "```"
echo "[config]"
echo "SCM_DO_BUILD_DURING_DEPLOYMENT=true"
echo "```"
echo "And ensure your composer.json and package.json scripts are set up for production builds."
