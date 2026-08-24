# Gunakan image PHP dengan ekstensi mysqli dan composer
FROM php:8.2-apache

# Install ekstensi yang dibutuhkan
RUN docker-php-ext-install mysqli

# Salin semua file ke dalam container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Install dependency dari composer.json
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install

# Aktifkan mod_rewrite untuk Apache
RUN a2enmod rewrite

# Expose port 80
EXPOSE 80
