#!/bin/sh

cd /var/www

echo "📄 Vérification des variables d'environnement Laravel..."
echo "APP_ENV=$APP_ENV"
echo "DB_HOST=$DB_HOST"
echo "DB_USERNAME=$DB_USERNAME"

# ── Guard production (F-022) ──────────────────────────────────────────────
if [ "${APP_ENV:-local}" = "production" ]; then
  if [ "${APP_DEBUG:-false}" = "true" ]; then
    echo "❌ APP_DEBUG=true interdit en production. Abandon."
    exit 1
  fi
  if [ "${FAKE_PAYMENT_MODE:-}" != "" ]; then
    echo "❌ FAKE_PAYMENT_MODE défini en production. Abandon."
    exit 1
  fi
fi

echo "📡 Attente de PostgreSQL sur $DB_HOST:$DB_PORT..."
max_try=30
try=0
until nc -z "$DB_HOST" "$DB_PORT"; do
  try=$((try+1))
  if [ "$try" -ge "$max_try" ]; then
    echo "❌ PostgreSQL toujours indisponible après $max_try tentatives. Abandon."
    exit 1
  fi
  echo "⏳ Tentative $try/$max_try..."
  sleep 1
done
echo "✅ PostgreSQL est prêt !"

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

# F-024 — retirer || true : une migration échouée doit bloquer le déploiement
echo "🗃️ Exécution des migrations Laravel..."
php artisan migrate --force
if [ $? -ne 0 ]; then
  echo "❌ Migration échouée — arrêt du déploiement."
  exit 1
fi

# F-018 — seed comptes ledger idempotent (obligatoire avant tout webhook financier)
echo "📒 Initialisation des comptes ledger..."
php artisan db:seed --class=LedgerAccountSeeder --force
if [ $? -ne 0 ]; then
  echo "❌ Seed comptes ledger échoué — arrêt du déploiement."
  exit 1
fi
echo "✅ Comptes ledger initialisés."

if [ "$#" -gt 0 ]; then
  echo "🚀 Lancement de la commande fournie : $*"
  exec "$@"
fi

echo "🚀 Aucune commande fournie, lancement PHP-FPM par défaut..."
exec php-fpm