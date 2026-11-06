#!/usr/bin/env bash
#
# Deploy the SPP application as the "deploy" user.
#
#   sudo -u deploy bash deploy.sh
#   sudo -u deploy bash deploy.sh --rollback
#
# The server is provisioned by provision.sh. The first run creates
# /var/www/spp/shared/.env from .env.example and generates APP_KEY.
#
set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/spp}"
CONFIG_FILE="$APP_ROOT/shared/deploy.env"

if [ -f "$CONFIG_FILE" ]; then
    # shellcheck disable=SC1090
    source "$CONFIG_FILE"
fi

APP_ROOT="${APP_ROOT:-/var/www/spp}"
REPO_URL="${REPO_URL:?REPO_URL belum diset di $CONFIG_FILE}"
BRANCH="${BRANCH:-main}"
PHP_VERSION="${PHP_VERSION:-8.4}"
PHP_BIN="${PHP_BIN:-/usr/bin/php${PHP_VERSION}}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
SKIP_ASSET_BUILD="${SKIP_ASSET_BUILD:-0}"

SHARED="$APP_ROOT/shared"
ENV_FILE="$SHARED/.env"
DB_PASSWORD_FILE="$SHARED/.db-password"
DB_NAME="${DB_NAME:-spp}"
DB_USER="${DB_USER:-spp}"

if [ "$(id -u)" -eq 0 ]; then
    echo "Jalankan deploy.sh sebagai user deploy, bukan root." >&2
    exit 1
fi

if [ ! -f "$DB_PASSWORD_FILE" ]; then
    echo "File $DB_PASSWORD_FILE tidak ditemukan. Jalankan provision.sh dulu." >&2
    exit 1
fi

if [ -z "${DOMAIN:-}" ]; then
    echo "DOMAIN belum diset di $CONFIG_FILE." >&2
    exit 1
fi

if [ "${1:-}" = "--rollback" ]; then
    CURRENT_TARGET="$(readlink -f "$APP_ROOT/current" 2>/dev/null || true)"
    PREVIOUS=""

    for release in $(ls -1dt "$APP_ROOT"/releases/* 2>/dev/null || true); do
        if [ "$(readlink -f "$release")" != "$CURRENT_TARGET" ]; then
            PREVIOUS="$release"
            break
        fi
    done

    if [ -z "$PREVIOUS" ]; then
        echo "Tidak ada release sebelumnya untuk rollback." >&2
        exit 1
    fi

    ln -sfn "$PREVIOUS" "$APP_ROOT/current"
    sudo systemctl reload "php${PHP_VERSION}-fpm"
    echo "Rollback ke $PREVIOUS"
    exit 0
fi

set_env() {
    local key="$1"
    local value="$2"
    local file="$3"

    if grep -qE "^${key}=" "$file"; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$file"
    else
        printf '%s=%s\n' "$key" "$value" >>"$file"
    fi
}

mkdir -p "$SHARED"/storage/{app/public,framework/{cache,sessions,views},logs}
mkdir -p "$APP_ROOT/releases"

RELEASE="$(date +%Y%m%d%H%M%S)"
RELEASE_DIR="$APP_ROOT/releases/$RELEASE"

echo "==> Clone release $RELEASE"
git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$RELEASE_DIR"
cd "$RELEASE_DIR"

echo "==> Siapkan .env"

if [ ! -f "$ENV_FILE" ]; then
    cp .env.example "$ENV_FILE"

    DB_PASS="$(cat "$DB_PASSWORD_FILE")"

    set_env APP_ENV production "$ENV_FILE"
    set_env APP_DEBUG false "$ENV_FILE"
    set_env APP_URL "https://${DOMAIN}" "$ENV_FILE"
    set_env APP_KEY "base64:$(openssl rand -base64 32)" "$ENV_FILE"
    set_env LOG_LEVEL warning "$ENV_FILE"

    set_env DB_CONNECTION mariadb "$ENV_FILE"
    set_env DB_HOST 127.0.0.1 "$ENV_FILE"
    set_env DB_PORT 3306 "$ENV_FILE"
    set_env DB_DATABASE "$DB_NAME" "$ENV_FILE"
    set_env DB_USERNAME "$DB_USER" "$ENV_FILE"
    set_env DB_PASSWORD "$DB_PASS" "$ENV_FILE"

    set_env SESSION_DRIVER file "$ENV_FILE"
    set_env SESSION_SECURE_COOKIE true "$ENV_FILE"
    set_env CACHE_STORE file "$ENV_FILE"
    set_env QUEUE_CONNECTION sync "$ENV_FILE"
    set_env FILESYSTEM_DISK local "$ENV_FILE"
    set_env BROADCAST_CONNECTION log "$ENV_FILE"
fi

chgrp www-data "$ENV_FILE"
chmod 640 "$ENV_FILE"

ln -sfn "$ENV_FILE" .env

echo "==> Siapkan storage bersama"
rm -rf storage
ln -sfn "$SHARED/storage" storage

echo "==> Install dependency PHP"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

if ! grep -q '^APP_KEY=base64:' "$ENV_FILE"; then
    set_env APP_KEY "base64:$(openssl rand -base64 32)" "$ENV_FILE"
fi

echo "==> Build asset frontend"

if [ "$SKIP_ASSET_BUILD" = "1" ]; then
    if [ ! -f public/build/manifest.json ]; then
        echo "SKIP_ASSET_BUILD=1 tetapi public/build/manifest.json tidak ada." >&2
        exit 1
    fi

    echo "SKIP_ASSET_BUILD=1, memakai public/build yang sudah ada."
else
    export NODE_OPTIONS="${NODE_OPTIONS:---max-old-space-size=1024}"
    npm ci --no-audit --no-fund
    npm run build
    rm -rf node_modules
fi

echo "==> Atur permission"

# File cache/session di shared/storage dibuat oleh PHP-FPM (www-data).
# User deploy bukan pemilik file-file itu, jadi jangan chgrp/chmod rekursif
# pada shared storage. Permission dasarnya sudah diatur provision.sh.
chmod -R ug+rwX "$RELEASE_DIR/bootstrap/cache"

echo "==> Migrasi dan cache"

cleanup() {
    "$PHP_BIN" artisan up >/dev/null 2>&1 || true
}

trap cleanup EXIT

"$PHP_BIN" artisan down --retry=60 || true
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link --force
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan filament:optimize

echo "==> Aktifkan release"

ln -sfn "$RELEASE_DIR" "$APP_ROOT/current"
sudo systemctl reload "php${PHP_VERSION}-fpm"
"$PHP_BIN" artisan up

trap - EXIT

echo "==> Bersihkan release lama"

ls -1dt "$APP_ROOT"/releases/* | tail -n +"$((KEEP_RELEASES + 1))" | xargs -r rm -rf

echo "Deploy selesai: $RELEASE_DIR"
