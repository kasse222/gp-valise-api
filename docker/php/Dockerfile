# ──────────────────────────────────────────────
# STAGE 1 : Builder Composer compatible Horizon
# ──────────────────────────────────────────────
FROM php:8.2-cli-alpine AS vendor

WORKDIR /app

RUN apk add --no-cache \
    git \
    unzip \
    zip \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    libpq-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    intl \
    bcmath \
    pcntl \
    posix

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-scripts

# ──────────────────────────────────────────────
# STAGE 2 : Image Laravel runtime (php-fpm)
# ──────────────────────────────────────────────
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash \
    zip \
    unzip \
    curl \
    git \
    postgresql-client \
    libpng \
    libjpeg-turbo \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    libpq-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    intl \
    bcmath \
    pcntl \
    posix \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

RUN addgroup -g 1000 laravel \
    && adduser -u 1000 -G laravel -s /bin/sh -D laravel

WORKDIR /var/www

COPY --from=vendor /app/vendor ./vendor

COPY . .

COPY .env.docker .env

COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN mkdir -p storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R laravel:laravel /var/www

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

USER laravel

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]