FROM node:18-alpine AS node-builder

WORKDIR /app
COPY package*.json ./

RUN npm install --no-audit

FROM serversideup/php:8.4-fpm-nginx-alpine AS prod

USER root

RUN install-php-extensions bcmath pdo_mysql pcntl intl

ENV AUTORUN_ENABLED="true" \
    AUTORUN_LARAVEL_MIGRATION_ISOLATION="false" \
    AUTORUN_LARAVEL_CONFIG_CACHE="false" \
    PHP_OPCACHE_ENABLE="1" \
    PHP_OPCACHE_MEMORY_CONSUMPTION=128 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000

COPY --chown=www-data:www-data composer.json composer.lock /var/www/html/

USER www-data
WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

COPY --chown=www-data:www-data . /var/www/html

WORKDIR /var/www/html

# Assicurati che bootstrap/cache esista e sia scrivibile
RUN mkdir -p bootstrap/cache && \
    chown -R www-data:www-data bootstrap/cache && \
    chmod -R 775 bootstrap/cache
    
STOPSIGNAL SIGTERM