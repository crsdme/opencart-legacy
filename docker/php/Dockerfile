FROM php:7.4-apache

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd zip mysqli

RUN a2enmod rewrite headers expires deflate

RUN docker-php-ext-install opcache

COPY docker/php/php.ini /usr/local/etc/php/php.ini

COPY docker/php/000-default.conf /etc/apache2/sites-available/000-default.conf
