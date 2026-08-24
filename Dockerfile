FROM php:8.2-apache

# Install library untuk GD, intl, dan zip
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli intl zip

# Salin semua file ke dalam container
COPY . /var/www/html/

WORKDIR /var/www/html/

# Install Composer dan dependency
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install --no-interaction --prefer-dist

# Aktifkan mod_rewrite untuk Apache
RUN a2enmod rewrite

EXPOSE 80
