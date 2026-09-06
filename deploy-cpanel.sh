#!/usr/bin/env bash

set -euo pipefail

APP_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_ROOT"

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
if [[ ! -e public/storage ]]; then
	php artisan storage:link
fi
php artisan optimize:clear
php artisan optimize