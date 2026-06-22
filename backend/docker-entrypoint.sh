#!/bin/sh
set -e

cd /var/www/html

if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Garante diretórios graváveis pelo PHP-FPM (www-data)
mkdir -p storage/framework/sessions \
         storage/framework/views \
         storage/framework/cache/data \
         storage/logs \
         bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Gera APP_KEY se ausente ou vazia
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
fi

php artisan config:clear
php artisan migrate --force --no-interaction 2>/dev/null || true

exec "$@"
