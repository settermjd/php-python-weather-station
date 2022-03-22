ARG ALPINE_VERSION=3.15
ARG PHP_VERSION=8.1.1

FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} as base

FROM base as development

RUN docker-php-ext-install pdo_mysql

RUN docker-php-ext-install pecl install xdebug && docker-php-ext-enable xdebug;

FROM base as dependencies

WORKDIR /var/www/html

COPY . .

RUN --mount=from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer  \
    composer install \
      --no-dev --no-ansi --no-plugins --no-progress --no-scripts \
      --classmap-authoritative --no-interaction \
      --quiet

FROM base as final

WORKDIR /var/www/html

COPY --from=dependencies /var/www/html .

RUN docker-php-ext-install pdo_mysql
