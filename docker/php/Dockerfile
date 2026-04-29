FROM php:8.3-fpm-alpine

ARG WWWUSER=1000
ARG WWWGROUP=1000

RUN set -eux; \
  apk add --no-cache \
    bash \
    git \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    $PHPIZE_DEPS; \
  docker-php-ext-configure gd --with-freetype --with-jpeg; \
  docker-php-ext-install -j"$(nproc)" \
    pdo_mysql \
    mbstring \
    intl \
    zip \
    bcmath \
    pcntl \
    exif \
    gd; \
  apk del --no-network $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN addgroup -g "${WWWGROUP}" -S www && adduser -u "${WWWUSER}" -S www -G www

WORKDIR /var/www/html

USER www
