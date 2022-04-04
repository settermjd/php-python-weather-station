ARG PHP_VERSION=8.1.1

FROM php:${PHP_VERSION}-fpm-buster as base

FROM base as final

COPY --from=composer:2.2.7 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
&& apt-get install -y --no-install-recommends \
        autoconf \
        gcc \
        git \
        libc-dev \
        libicu-dev \
        libxml2-dev \
        libzip-dev \
        make \
        pkg-config \
        unzip \
        zlib1g-dev \
&& apt-get clean \
&& rm -rf /var/lib/apt/lists/*

RUN pecl install \
    xdebug \
&& docker-php-ext-install \
    intl \
    sockets \
    simplexml \
    zip \
&& docker-php-ext-enable \
    intl \
    sockets \
    xdebug

WORKDIR /var/www/html
