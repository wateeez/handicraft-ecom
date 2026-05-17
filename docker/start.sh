#!/bin/bash
php artisan migrate --force
service nginx start
php-fpm