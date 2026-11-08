FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip bcmath gd exif \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --classmap-authoritative --no-scripts
COPY . .
RUN mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative \
    && php artisan package:discover --no-interaction \
    && php artisan filament:assets --no-interaction

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/pts-laravel.conf \
    && a2enconf pts-laravel

EXPOSE 80
HEALTHCHECK --interval=15s --timeout=8s --start-period=30s --retries=4 \
    CMD php -r '$c=stream_context_create(["http"=>["timeout"=>5,"ignore_errors"=>true]]); $b=@file_get_contents("http://127.0.0.1/health",false,$c); exit($b!==false && isset($http_response_header[0]) && str_contains($http_response_header[0]," 200 ") ? 0 : 1);'
