FROM node:22-alpine AS frontend
WORKDIR /src
COPY backend/package*.json ./
RUN npm ci
COPY backend/resources ./resources
COPY backend/vite.config.js ./vite.config.js
RUN npm run build

FROM composer:2 AS composer
WORKDIR /src
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.3-fpm-alpine AS runtime
RUN apk add --no-cache icu-libs libpq libzip curl su-exec \
    && apk add --no-cache --virtual .build-deps icu-dev postgresql-dev libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install bcmath intl opcache pcntl pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps
WORKDIR /var/www/html
COPY backend ./
COPY --from=composer /src/vendor ./vendor
COPY --from=frontend /src/public/build ./public/build
COPY deploy/docker/php.ini /usr/local/etc/php/conf.d/htsms.ini
COPY deploy/docker/entrypoint.sh /usr/local/bin/htsms-entrypoint
RUN chmod +x /usr/local/bin/htsms-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache
ENTRYPOINT ["htsms-entrypoint"]
CMD ["php-fpm", "-F"]
