FROM php:8.1-apache

RUN apt-get update -o Acquire::Retries=3 \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
		libwebp-dev \
		webp \
        libzip-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j2 calendar exif gd mbstring mysqli pdo_mysql zip \
    && a2enmod rewrite headers expires \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php81/local-php.ini /usr/local/etc/php/conf.d/99-pts-local.ini
COPY domains/phucthinhsolar.com/public_html/ /var/www/html/

RUN mkdir -p /var/www/html/caches /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/caches /var/www/html/logs /var/www/html/upload /var/www/html/thumbs

WORKDIR /var/www/html
