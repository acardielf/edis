FROM php:8.3-alpine

RUN apk add --no-cache --update \
    g++ \
    $PHPIZE_DEPS \
    icu-dev \
    linux-headers \
    zip

RUN docker-php-ext-install  \
    intl \
    opcache

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync && install-php-extensions http

RUN pecl install xdebug && docker-php-ext-enable xdebug

WORKDIR /var/www/src

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
