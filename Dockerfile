# SYNPHONIE — image prod : PHP-FPM + Nginx dans un seul conteneur.
# Assets compilés via Node, code via Composer, écoute sur $PORT (Render).

# ---------- 1. Assets frontend ----------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- 2. Application PHP ----------
FROM php:8.3-fpm-bookworm
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor curl unzip \
        libpq-dev libzip-dev zlib1g-dev libpng-dev libicu-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer install --no-dev --no-interaction --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/sites-enabled/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["entrypoint.sh"]
