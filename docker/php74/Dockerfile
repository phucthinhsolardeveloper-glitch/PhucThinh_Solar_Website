FROM php:7.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" calendar exif gd mbstring mysqli pdo_mysql zip \
    && a2enmod rewrite headers expires \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php74/local-php.ini /usr/local/etc/php/conf.d/99-pts-local.ini
COPY domains/phucthinhsolar.com/public_html/ /var/www/html/

RUN mkdir -p /var/www/html/caches /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/caches /var/www/html/logs /var/www/html/upload /var/www/html/thumbs

WORKDIR /var/www/html
