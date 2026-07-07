FROM php:8.4-fpm AS base

RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev libicu-dev libexif-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mbstring exif bcmath gd zip intl xml pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm --version

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY package.json package-lock.json vite.config.js ./
COPY resources/ resources/
RUN npm ci && npm run build

COPY . .

ARG APP_KEY=base64:placeholderkeyforbuildonly==
ENV APP_KEY=${APP_KEY} APP_ENV=production
RUN composer dump-autoload && php artisan package:discover --ansi

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

FROM nginx:alpine AS nginx
COPY --from=base /var/www/public /var/www/public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
