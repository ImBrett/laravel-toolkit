# syntax=docker/dockerfile:1.7
ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-cli AS base

# system deps
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libsqlite3-dev sqlite3 \
 && docker-php-ext-install pdo pdo_sqlite \
 && rm -rf /var/lib/apt/lists/*

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# xdebug for local (off by default via XDEBUG_MODE=off)
RUN pecl install xdebug && docker-php-ext-enable xdebug

# alternatively, pcov (much faster coverage); enable one or the other
# RUN pecl install pcov && docker-php-ext-enable pcov

# user
ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} app && useradd -m -u ${UID} -g ${GID} app

# Composer home/cache for user "app"
ENV COMPOSER_HOME=/home/app/.composer
ENV COMPOSER_CACHE_DIR=/home/app/.composer/cache
RUN mkdir -p ${COMPOSER_CACHE_DIR} && chown -R app:app /home/app/.composer

# php.ini (kept minimal; xdebug controlled via env)
COPY --chown=app:app php.ini /usr/local/etc/php/conf.d/php.ini

USER app
WORKDIR /app
