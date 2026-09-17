FROM php:7.4-apache

RUN sed -ri \
        -e 's!http://deb.debian.org/debian-security!http://snapshot.debian.org/archive/debian-security/20221114T000000Z!g' \
        -e 's!http://deb.debian.org/debian!http://snapshot.debian.org/archive/debian/20221114T000000Z!g' \
        /etc/apt/sources.list \
    && apt-get update -o Acquire::Check-Valid-Until=false -o Acquire::Retries=3 \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        webp \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" calendar curl exif gd mbstring mysqli opcache pdo_mysql zip \
    && a2enmod deflate expires headers remoteip rewrite \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

COPY app/php-production.ini /usr/local/etc/php/conf.d/99-pts-production.ini
COPY app/apache-security.conf /etc/apache2/conf-available/pts-security.conf
RUN a2enconf pts-security \
    && printf '%s\n' 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

COPY public_html/ /var/www/html/

RUN mkdir -p \
        /var/www/html/assets/caches \
        /var/www/html/caches \
        /var/www/html/logs \
        /var/www/html/thumbs \
        /var/www/html/upload \
        /var/www/html/watermark \
    && find /var/www/html -type d -exec chmod 0755 {} + \
    && find /var/www/html -type f -exec chmod 0644 {} + \
    && chown -R www-data:www-data \
        /var/www/html/assets/caches \
        /var/www/html/caches \
        /var/www/html/logs \
        /var/www/html/thumbs \
        /var/www/html/upload \
        /var/www/html/watermark

WORKDIR /var/www/html
