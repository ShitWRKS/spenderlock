FROM node:24-alpine AS node-builder

WORKDIR /app
COPY package*.json ./
COPY vite.config.js ./
COPY resources resources
COPY public public

# Install node deps and build frontend assets
RUN npm install && \
    npm run build

FROM serversideup/php:8.4-fpm-nginx-alpine AS prod

USER root

RUN install-php-extensions bcmath pdo_pgsql pcntl intl

ENV AUTORUN_ENABLED="true" \
    AUTORUN_LARAVEL_MIGRATION_ISOLATION="false" \
    AUTORUN_LARAVEL_CONFIG_CACHE="false" \
    AUTORUN_LARAVEL_SETUP_DEFAULT_TENANT="true" \
    PHP_OPCACHE_ENABLE="1" \
    PHP_OPCACHE_MEMORY_CONSUMPTION=128 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000

COPY --chown=www-data:www-data composer.json composer.lock /var/www/html/

USER www-data
WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

COPY --chown=www-data:www-data . /var/www/html

RUN php artisan filament:assets

# Copia gli asset frontend buildati dalla stage node-builder
COPY --from=node-builder --chown=www-data:www-data /app/public /var/www/html/public

# Copia e rendi eseguibile lo script di setup
COPY --chown=www-data:www-data --chmod=755 docker-setup.sh /var/www/html/

# Copia l'hook di entrypoint per l'esecuzione automatica
USER root
COPY --chmod=755 .docker/entrypoint.d/50-setup-tenant.sh /etc/entrypoint.d/
USER www-data

WORKDIR /var/www/html

# Assicurati che bootstrap/cache esista e sia scrivibile
RUN mkdir -p bootstrap/cache && \
    chown -R www-data:www-data bootstrap/cache && \
    chmod -R 775 bootstrap/cache && \
    rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
    
STOPSIGNAL SIGTERM