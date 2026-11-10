# Local development image, ONE container: PHP 8.4 CLI with the extensions Laravel and Filament need, plus MariaDB
# (Debian 12 ships 10.11, the version production runs). local-entrypoint.sh starts both.
FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip mariadb-server libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip bcmath gd exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY upload.ini /usr/local/etc/php/conf.d/pts-upload.ini
COPY local-entrypoint.sh /usr/local/bin/local-entrypoint.sh
RUN chmod +x /usr/local/bin/local-entrypoint.sh && mkdir -p /seed

WORKDIR /app
EXPOSE 8000
VOLUME /var/lib/mysql
ENTRYPOINT ["/usr/local/bin/local-entrypoint.sh"]
