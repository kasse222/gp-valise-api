#!/bin/sh

cd /var/www

echo "📄 Vérification des variables d'environnement Laravel..."
echo "APP_ENV=$APP_ENV"
echo "DB_HOST=$DB_HOST"
echo "DB_USERNAME=$DB_USERNAME"

echo "📡 Attente de MySQL sur $DB_HOST:$DB_PORT..."
max_try=30
try=0
until nc -z "$DB_HOST" "$DB_PORT"; do
  try=$((try+1))
  if [ "$try" -ge "$max_try" ]; then
    echo "❌ MySQL toujours indisponible après $max_try tentatives. Abandon."
    exit 1
  fi
  echo "⏳ Tentative $try/$max_try..."
  sleep 1
done
echo "✅ MySQL est prêt !"

if [ ! -f artisan ]; then
  echo "❌ Fichier artisan manquant — arrêt du script."
  exit 1
fi

echo "🎛 Configuration de Laravel..."

echo "🧹 Nettoyage config & cache..."
php artisan optimize:clear || true

echo "🔁 Génération des caches Laravel..."
php artisan config:cache
php artisan view:cache

if [ "${APP_ENV:-local}" = "production" ]; then
  echo "🛣️ Génération du cache des routes (production uniquement)..."
  php artisan route:cache
else
  echo "ℹ️ route:cache ignoré hors production"
fi

echo "📦 Découverte des packages..."
php artisan package:discover --ansi || true

echo "🔗 Lien symbolique de storage..."
if [ ! -L public/storage ]; then
  php artisan storage:link
fi

echo "🔐 Vérification des permissions..."
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache || true

echo "🗃️ Exécution des migrations Laravel..."
php artisan migrate --force || true

if [ "$#" -gt 0 ]; then
  echo "🚀 Lancement de la commande fournie : $*"
  exec "$@"
fi

echo "🚀 Aucune commande fournie, lancement PHP-FPM par défaut..."
exec php-fpm