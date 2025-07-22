#!/bin/bash

# Clear cache for DDEV
ddev exec php artisan config:clear &&
ddev exec php artisan cache:clear &&
ddev exec php artisan optimize:clear

# Clear cache for Native
php artisan config:clear &&
php artisan cache:clear &&
php artisan optimize:clear

echo "Cache cleared for both DDEV and Native environments."
