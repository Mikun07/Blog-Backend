#!/usr/bin/env sh
set -e

export COMPOSER_ALLOW_SUPERUSER="${COMPOSER_ALLOW_SUPERUSER:-1}"
export COMPOSER_HOME="${COMPOSER_HOME:-/tmp/composer-cache}"

mkdir -p bootstrap/cache \
    storage/framework/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chmod -R ug+rw bootstrap/cache storage 2>/dev/null || true

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -f .env ] && ! grep -Eq '^APP_KEY=.+$' .env; then
    php artisan key:generate --ansi --force
fi

exec "$@"
