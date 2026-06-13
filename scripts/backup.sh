#!/bin/sh

# ───────────────────────────────────────────────
# 🧠 Configuration
# ───────────────────────────────────────────────
DATE=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_DIR="storage/backups"
LOG_FILE="$BACKUP_DIR/backup.log"
DB_FILE="$BACKUP_DIR/db_backup_$DATE.sql"
ARCHIVE_FILE="$BACKUP_DIR/backup_$DATE.tar.gz"
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

# ───── 📦 Dump PostgreSQL
echo "* 📦 Dump PostgreSQL..." | tee -a "$LOG_FILE"

if ! PGPASSWORD="$DB_PASSWORD" pg_dump \
    -h "$DB_HOST" \
    -p "${DB_PORT:-5432}" \
    -U "$DB_USERNAME" \
    "$DB_DATABASE" \
    --no-password \
    --format=plain \
    --no-owner \
    --no-acl \
    -f "$DB_FILE"; then
    echo "❌ Échec du dump PostgreSQL à $DATE" | tee -a "$LOG_FILE"
    exit 1
fi

echo "✅ Dump PostgreSQL OK : $DB_FILE" | tee -a "$LOG_FILE"

# ───────────────────────────────────────────────
# 🗃️ Archive du dump + storage
# ───────────────────────────────────────────────
echo "* 🗃️ Compression des données..." | tee -a "$LOG_FILE"

if ! tar -czf "$ARCHIVE_FILE" "$DB_FILE" storage/app; then
    echo "❌ Échec de la compression à $DATE" | tee -a "$LOG_FILE"
    exit 1
fi

echo "✅ Archive créée : $ARCHIVE_FILE" | tee -a "$LOG_FILE"

# ───────────────────────────────────────────────
# 🧹 Rotation — supprime les backups > 30 jours
# ───────────────────────────────────────────────
echo "* 🧹 Rotation des anciens backups (> ${RETENTION_DAYS}j)..." | tee -a "$LOG_FILE"
find "$BACKUP_DIR" -name "backup_*.tar.gz" -mtime +$RETENTION_DAYS -delete
echo "✅ Rotation terminée" | tee -a "$LOG_FILE"

# ───────────────────────────────────────────────
# 🗑️ Nettoyage dump brut
# ───────────────────────────────────────────────
rm -f "$DB_FILE"

echo "✅ Sauvegarde complète : $ARCHIVE_FILE à $DATE" | tee -a "$LOG_FILE"