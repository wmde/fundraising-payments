FROM php:8.1-alpine as base

RUN apk add --no-cache bash
RUN docker-php-ext-install pdo pdo_mysql

FROM base as debug
RUN apk add --no-cache $PHPIZE_DEPS
RUN pecl install xdebug-3.1.2 \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
