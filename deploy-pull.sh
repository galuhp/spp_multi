#!/usr/bin/env bash
#
# Deploy dengan `git pull` di direktori aplikasi saat ini.
# Berbeda dengan deploy.sh yang membuat release baru lewat `git clone`.
#
# Contoh:
#   sudo -u deploy bash deploy-pull.sh
#   APP_ROOT=/var/www/spp/current BRANCH=main PHP_BIN=/usr/bin/php8.5 bash deploy-pull.sh
#   BUILD_ASSETS=1 bash deploy-pull.sh   # kalau ada perubahan CSS/JS
#
set -euo pipefail

SCRIPT_PATH="${BASH_SOURCE[0]}"
if command -v readlink >/dev/null 2>&1; then
    SCRIPT_PATH="$(readlink -f "$SCRIPT_PATH")"
fi

SCRIPT_DIR="$(cd "$(dirname "$SCRIPT_PATH")" && pwd)"

# Ambil APP_ROOT dari command line sebelum file konfigurasi dibaca, supaya
# nilai dari deploy.env tidak mengarah ke folder root release.
PULL_APP_ROOT="${APP_ROOT:-$SCRIPT_DIR}"
BRANCH="${BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
BUILD_ASSETS="${BUILD_ASSETS:-0}"

# Opsional: ambil konfigurasi deploy yang sama dengan deploy.sh.
CONFIG_FILE="${DEPLOY_ENV_FILE:-/var/www/spp/shared/deploy.env}"
if [ -f "$CONFIG_FILE" ]; then
    # shellcheck disable=SC1090
    source "$CONFIG_FILE"
fi

APP_ROOT="$PULL_APP_ROOT"

if [ "$(id -u)" -eq 0 ]; then
    echo "Jalankan sebagai user deploy, bukan root." >&2
    exit 1
fi

cd "$APP_ROOT"

if [ ! -d .git ]; then
    echo "$APP_ROOT bukan repository git." >&2
    exit 1
fi

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    echo "PHP tidak ditemukan: $PHP_BIN" >&2
    exit 1
fi

if ! command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
    echo "Composer tidak ditemukan: $COMPOSER_BIN" >&2
    exit 1
fi

echo "==> Aplikasi: $APP_ROOT"
echo "==> Branch  : $BRANCH"
echo "==> PHP     : $("$PHP_BIN" -r 'echo PHP_VERSION;')"

echo "==> Maintenance mode"
"$PHP_BIN" artisan down --retry=60 || true

cleanup() {
    "$PHP_BIN" artisan up >/dev/null 2>&1 || true
}
trap cleanup EXIT

echo "==> Git fetch & pull"
git fetch origin "$BRANCH"
git pull --ff-only origin "$BRANCH"

echo "==> Install dependency PHP"
"$COMPOSER_BIN" install --no-dev --prefer-dist --no-interaction --optimize-autoloader

if [ "$BUILD_ASSETS" = "1" ]; then
    echo "==> Build asset frontend"
    npm ci --no-audit --no-fund
    npm run build
fi

echo "==> Migrasi database"
"$PHP_BIN" artisan migrate --force

echo "==> Storage link"
"$PHP_BIN" artisan storage:link --force || true

echo "==> Bersihkan cache lama"
"$PHP_BIN" artisan optimize:clear

echo "==> Cache ulang konfigurasi, route, view, event"
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan event:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan filament:optimize || true

echo "==> Restart queue worker (jika ada)"
"$PHP_BIN" artisan queue:restart || true

echo "==> Permission cache"
chmod -R ug+rwX bootstrap/cache

echo "==> Selesai"
"$PHP_BIN" artisan up
trap - EXIT
