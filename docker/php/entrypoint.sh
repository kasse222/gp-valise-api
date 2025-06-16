#!/bin/sh

set -e

echo "⏳ Attente de la base de données MySQL..."
until nc -z mysql 3306; do
  sleep 1
done

echo "✅ Base de données disponible, initialisation Laravel..."

# Exécution des scripts artisan
echo "📦 Découverte des packages..."
php artisan package:discover --ansi || true

echo "🔧 Cache config Laravel..."
php artisan config:clear || true
php artisan config:cache || true

echo "🗄️  Migration des tables..."
php artisan migrate --force || true

echo "🔗 Lien symbolique de stockage..."
php artisan storage:link || true

echo "🚀 Lancement de PHP-FPM..."
exec php-fpm
