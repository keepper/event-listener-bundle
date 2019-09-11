# Для начала указываем исходный образ, он будет использован как основа
FROM php:7.2-fpm-alpine3.8

ENV PHPIZE_DEPS \
    autoconf \
    cmake \
    file \
    g++ \
    gcc \
    libc-dev \
    pcre-dev \
    make \
    git \
    pkgconf \
    re2c \
    wget \
    bash

RUN apk add --no-cache --virtual .composer-runtime-deps git

RUN set -xe \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
    && pecl install xdebug-2.6.1 \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-configure bcmath --enable-bcmath \
    && docker-php-ext-configure mbstring --enable-mbstring \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        pcntl \
        mbstring \
        iconv

RUN wget https://getcomposer.org/installer -O /tmp/composer-setup.php \
    && wget https://composer.github.io/installer.sig -O /tmp/composer-setup.sig \
    && php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" \
    && php /tmp/composer-setup.php --no-ansi --install-dir=/usr/local/bin --filename=composer --snapshot \
    && rm -rf /tmp/composer-setup.php /tmp/composer-setup.sig

# Copy configuration
RUN apk del .build-deps \
    && rm -rf /tmp/* \
    && rm -rf /app \
    && mkdir /app

ADD php.ini /usr/local/etc/php/conf.d/40-custom.ini

RUN adduser -D -g '' developer

USER developer
VOLUME ["/var/www/"]
WORKDIR /var/www/