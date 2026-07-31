FROM php:8.3-fpm

# Set working directory to /var/www
WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql

COPY /html /var/www/html
COPY /src /var/www/src

# Copy composer files first for layer caching
COPY composer.json /var/www/composer.json

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install dependencies
RUN composer install --optimize-autoloader --no-scripts


RUN mkdir -p /var/www/html/iiif_manifests
RUN mkdir -p /var/www/logs

RUN chown -R www-data:www-data /var/www
RUN chmod -R 775 /var/www/logs

RUN chmod +x /var/www/vendor/bin/phpunit

COPY ./php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./php/php.ini /usr/local/etc/php/php.ini

COPY ./php/php-fpm-entrypoint.sh /usr/local/bin/
RUN sed -i 's/\r//' /usr/local/bin/php-fpm-entrypoint.sh && chmod +x /usr/local/bin/php-fpm-entrypoint.sh

ENTRYPOINT ["php-fpm-entrypoint.sh"]


