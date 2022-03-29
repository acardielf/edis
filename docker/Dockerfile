FROM php:8.0-apache

RUN pecl install xdebug && docker-php-ext-enable xdebug

WORKDIR /var/www/src

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer