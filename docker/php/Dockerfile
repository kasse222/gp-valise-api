# ──────────────────────────────────────────────
# STAGE 1 : Builder Composer (dépendances Laravel)
# ──────────────────────────────────────────────
FROM composer:2.7 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-scripts

# ──────────────────────────────────────────────
# STAGE 2 : Image Laravel (php-fpm)
# ──────────────────────────────────────────────
FROM php:8.2-fpm-alpine

# Dépendances système + extensions PHP requises
RUN apk add --no-cache \
    bash \
    zip \
    unzip \
    curl \
    git \
    mysql-client \
    libpng \
    libjpeg-turbo \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    intl \
    bcmath \
    pcntl \
    posix \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Création d’un user applicatif non-root
RUN addgroup -g 1000 laravel \
    && adduser -u 1000 -G laravel -s /bin/sh -D laravel

# Répertoire de travail
WORKDIR /var/www

# Copier vendor depuis le stage builder
COPY --from=vendor /app/vendor ./vendor

# Copier le code applicatif
COPY . .

# Copier la configuration d’environnement Docker
COPY .env.docker .env

# Droits nécessaires à Laravel
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R laravel:laravel storage bootstrap/cache

# Entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER laravel

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]