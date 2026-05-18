#!/bin/bash
set -e

# 1. Bind to Render's dynamic port
PORT=${PORT:-80}
sed -i "s/listen 80;/listen $PORT;/g" /etc/nginx/sites-enabled/default

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
service nginx start
php-fpm