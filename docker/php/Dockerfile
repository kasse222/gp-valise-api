# ──────────────────────────────────────────────
# STAGE 1 : Builder Composer compatible Horizon
# ──────────────────────────────────────────────
FROM php:8.2-cli-alpine AS vendor

WORKDIR /app

# Dépendances système + extensions PHP nécessaires au composer install
RUN apk add --no-cache \
    git \
    unzip \
    zip \
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
    posix

# Copier Composer depuis l'image officielle
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copier les fichiers de dépendances
COPY composer.json composer.lock ./

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-scripts

# ──────────────────────────────────────────────
# STAGE 2 : Image Laravel runtime (php-fpm)
# ──────────────────────────────────────────────
FROM php:8.2-fpm-alpine

# Dépendances système + extensions PHP runtime
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

# Création d’un user non-root
RUN addgroup -g 1000 laravel \
    && adduser -u 1000 -G laravel -s /bin/sh -D laravel

WORKDIR /var/www

# Copier les vendors depuis le stage builder
COPY --from=vendor /app/vendor ./vendor

# Copier le code applicatif
COPY . .

# Environnement Docker local
COPY .env.docker .env

# Permissions Laravel
RUN mkdir -p storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R laravel:laravel /var/www

# Entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER laravel

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]