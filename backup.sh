#!/usr/bin/env bash
#
# Backup database and uploaded files.
#
# Local backup:
#   sudo bash backup.sh
#
# Offsite backup with restic:
#   Create /etc/spp/backup.env (root, chmod 600) with at least:
#
#     RESTIC_REPOSITORY=s3:https://s3.us-west-004.backblazeb2.com/bucket-name
#     RESTIC_PASSWORD_FILE=/etc/spp/restic-password
#     AWS_ACCESS_KEY_ID=...
#     AWS_SECRET_ACCESS_KEY=...
#
# Then run again. The cron entry created by provision.sh runs this nightly.
#
set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/spp}"

if [ -f "$APP_ROOT/shared/deploy.env" ]; then
    # shellcheck disable=SC1091
    source "$APP_ROOT/shared/deploy.env"
fi

BACKUP_DIR="${BACKUP_DIR:-/var/backups/spp}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
DB_NAME="${DB_NAME:-spp}"
DB_CNF="$APP_ROOT/shared/.my.cnf"

if [ ! -f "$DB_CNF" ]; then
    echo "File $DB_CNF tidak ditemukan. Jalankan provision.sh dulu." >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

DUMP="$BACKUP_DIR/spp-$(date +%F_%H%M%S).sql.gz"

echo "==> Dump database"
mariadb-dump \
    --defaults-extra-file="$DB_CNF" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    "$DB_NAME" | gzip >"$DUMP"

chmod 600 "$DUMP"

if [ -f /etc/spp/backup.env ]; then
    # shellcheck disable=SC1091
    source /etc/spp/backup.env
fi

if [ -n "${RESTIC_REPOSITORY:-}" ]; then
    echo "==> Backup offsite dengan restic"

    BACKUP_PATHS=("$DUMP" "$APP_ROOT/shared/storage/app")

    if [ -f "$APP_ROOT/shared/.env" ]; then
        BACKUP_PATHS+=("$APP_ROOT/shared/.env")
    fi

    restic backup "${BACKUP_PATHS[@]}" --tag spp

    restic forget \
        --keep-daily 14 \
        --keep-weekly 8 \
        --keep-monthly 6 \
        --prune
else
    echo "RESTIC_REPOSITORY belum diset; hanya backup lokal yang dibuat."
fi

find "$BACKUP_DIR" -type f -name 'spp-*.sql.gz' -mtime +"$RETENTION_DAYS" -delete

echo "Backup selesai: $DUMP"
