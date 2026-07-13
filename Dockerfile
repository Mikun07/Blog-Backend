# syntax=docker/dockerfile:1

FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        git \
        libcurl4-openssl-dev \
        libonig-dev \
        libsqlite3-dev \
        libxml2-dev \
        libzip-dev \
        sqlite3 \
        unzip \
    && docker-php-ext-install \
        bcmath \
        curl \
        dom \
        mbstring \
        pcntl \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer-cache

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

COPY . .
COPY docker/entrypoint.sh /usr/local/bin/blog-backend-entrypoint

RUN chmod +x /usr/local/bin/blog-backend-entrypoint \
    && composer dump-autoload --optimize \
    && php artisan package:discover --ansi

EXPOSE 8000

ENTRYPOINT ["blog-backend-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
