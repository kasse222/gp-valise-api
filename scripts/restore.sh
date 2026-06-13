#!/bin/sh

# ───────────────────────────────────────────────
# 🔄 Restore PostgreSQL depuis une archive backup
# Usage : ./restore.sh storage/backups/backup_2026-06-13_20-00-00.tar.gz
# ───────────────────────────────────────────────

ARCHIVE_FILE="$1"

if [ -z "$ARCHIVE_FILE" ]; then
    echo "❌ Usage : $0 <chemin_archive.tar.gz>"
    exit 1
fi

if [ ! -f "$ARCHIVE_FILE" ]; then
    echo "❌ Archive introuvable : $ARCHIVE_FILE"
    exit 1
fi

DATE=$(date +"%Y-%m-%d_%H-%M-%S")
RESTORE_DIR="storage/backups/restore_$DATE"

mkdir -p "$RESTORE_DIR"

# ───── Extraction
echo "* 📂 Extraction de $ARCHIVE_FILE..."
if ! tar -xzf "$ARCHIVE_FILE" -C "$RESTORE_DIR"; then
    echo "❌ Échec de l'extraction"
    exit 1
fi

# ───── Trouver le fichier SQL
DB_FILE=$(find "$RESTORE_DIR" -name "db_backup_*.sql" | head -1)

if [ -z "$DB_FILE" ]; then
    echo "❌ Aucun fichier SQL trouvé dans l'archive"
    exit 1
fi

echo "* 📦 Fichier SQL trouvé : $DB_FILE"

# ───── Confirmation
echo "⚠️  ATTENTION : Cette opération va écraser la base '$DB_DATABASE' sur $DB_HOST"
printf "Confirmer ? (yes/no) : "
read CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "❌ Restore annulé"
    exit 1
fi

# ───── Restore PostgreSQL
echo "* 🔄 Restore PostgreSQL en cours..."
if ! PGPASSWORD="$DB_PASSWORD" psql \
    -h "$DB_HOST" \
    -p "${DB_PORT:-5432}" \
    -U "$DB_USERNAME" \
    "$DB_DATABASE" \
    --no-password \
    -f "$DB_FILE"; then
    echo "❌ Échec du restore PostgreSQL"
    exit 1
fi

echo "✅ Restore terminé depuis $ARCHIVE_FILE"

# ───── Nettoyage
rm -rf "$RESTORE_DIR"