# ────────────────────────────────────────────────────────────────
# STAGE 1 : Builder Composer (dependencies sans node_modules)
# ────────────────────────────────────────────────────────────────
FROM composer:2.7 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-scripts

# Stage 2 – App final (PHP + Laravel)
FROM php:8.2-fpm-alpine

# Sécurité + légèreté : installation minimale
RUN apk --no-cache add \
    bash \
    zip \
    unzip \
    curl \
    libpng \
    libjpeg-turbo \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    mysql-client \
    git \
    && docker-php-ext-install pdo pdo_mysql zip intl bcmath

# Création d’un user non root
RUN addgroup -g 1000 laravel && adduser -G laravel -g "Laravel User" -s /bin/sh -D laravel

WORKDIR /var/www

#  Copie les dépendances vendor de l’étape précédente
COPY --from=vendor /app/vendor ./vendor

# Copie du code
COPY . .

# Droits adaptés
RUN chown -R laravel:laravel /var/www

USER laravel

EXPOSE 9000
CMD ["php-fpm"]
