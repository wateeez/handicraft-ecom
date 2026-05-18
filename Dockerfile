FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    nginx git curl zip unzip \
    libpq-dev libpng-dev libonig-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/sites-enabled/default

EXPOSE 80

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh
CMD ["/start.sh"]
