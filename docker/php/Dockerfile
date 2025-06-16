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

# Dépendances système PHP requises pour Laravel
RUN apk add --no-cache \
    bash zip unzip curl git mysql-client \
    libpng libjpeg-turbo libzip-dev \
    oniguruma-dev icu-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip intl bcmath

# Création d’un user sécurisé
RUN addgroup -g 1000 laravel && adduser -u 1000 -G laravel -s /bin/sh -D laravel

# Définir le dossier de travail (racine Laravel)
WORKDIR /var/www

# Copier uniquement vendor de l’étape 1
COPY --from=vendor /app/vendor ./vendor

# Copier tout le projet
COPY . .

# Copier le .env spécifique Docker
COPY .env.docker .env

# Donner les droits aux dossiers nécessaires
RUN chmod -R 775 storage bootstrap/cache && \
    chown -R laravel:laravel storage bootstrap/cache

# Copier le script d’entrée et le rendre exécutable
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER laravel

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
