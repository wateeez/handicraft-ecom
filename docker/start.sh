#!/bin/bash
set -e

# 1. Bind to Render's dynamic port
PORT=${PORT:-80}

# 2. Fix storage permissions at runtime
mkdir -p storage/framework/sessions \
         storage/framework/views \
         storage/framework/cache \
         storage/logs \
         bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 3. Cache config and views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Migrate database safely
php artisan migrate --force

# 5. Start services
echo "Starting PHP built-in server on port ${PORT:-80}..."
php -S 0.0.0.0:${PORT:-80} -t public