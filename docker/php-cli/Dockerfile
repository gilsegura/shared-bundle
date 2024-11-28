FROM php:8.3-cli-alpine

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions  \
    bcmath \
    exif \
    intl \
    opcache \
    pcntl \
    apcu \
    pdo_mysql \
    amqp \
    xdebug

RUN install-php-extensions @composer

WORKDIR /var/www

