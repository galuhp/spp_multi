#!/usr/bin/env bash
#
# Deploy zero-downtime backend Go E-SPP dengan strategi blue/green.
#
#   sudo -u spp bash deploy-go.sh
#   sudo -u spp bash deploy-go.sh --rollback
#
# Alur deploy:
#   1. clone release baru dan build binary
#   2. arahkan symlink current ke release baru
#   3. hidupkan slot yang tidak menerima trafik
#   4. tunggu /readyz sehat
#   5. pindahkan upstream Nginx secara atomik
#   6. matikan slot lama
#
set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/spp-api}"
APP_USER="${APP_USER:-spp}"
CONFIG_FILE="$APP_ROOT/shared/deploy.env"

if [[ -f "$CONFIG_FILE" ]]; then
    # shellcheck disable=SC1090
    source "$CONFIG_FILE"
fi

REPO_URL="${REPO_URL:-}"
BRANCH="${BRANCH:-main}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
GO_SUBDIR="${GO_SUBDIR:-backend-go}"
GO_BIN="${GO_BIN:-/usr/local/go/bin/go}"
SYSTEMCTL="${SYSTEMCTL:-$(command -v systemctl)}"

SHARED="$APP_ROOT/shared"
CURRENT_LINK="$APP_ROOT/current"
ACTIVE_FILE="$SHARED/active-slot"

if [[ "$(id -u)" -eq 0 ]]; then
    echo "Jalankan deploy-go.sh sebagai user ${APP_USER}, bukan root." >&2
    exit 1
fi

if [[ ! -x "$GO_BIN" ]]; then
    echo "Go tidak ditemukan di $GO_BIN. Jalankan provision-go.sh dulu." >&2
    exit 1
fi

if [[ ! -f "$ACTIVE_FILE" ]]; then
    echo "File slot aktif $ACTIVE_FILE tidak ditemukan. Jalankan provision-go.sh dulu." >&2
    exit 1
fi

slot_port() {
    case "$1" in
        blue) echo 8081 ;;
        green) echo 8082 ;;
        *) return 1 ;;
    esac
}

link_release() {
    local target="$1"

    ln -sfn "$target" "$APP_ROOT/current.next"
    mv -Tf "$APP_ROOT/current.next" "$CURRENT_LINK"
}

wait_ready() {
    local port="$1"
    local attempt

    for attempt in $(seq 1 30); do
        if curl -fsS --max-time 2 "http://127.0.0.1:${port}/readyz" >/dev/null 2>&1; then
            return 0
        fi

        sleep 1
    done

    return 1
}

activate_inactive_slot() {
    local active inactive port

    active="$(cat "$ACTIVE_FILE" 2>/dev/null || echo blue)"
    if [[ "$active" == "blue" ]]; then
        inactive="green"
    else
        inactive="blue"
    fi

    port="$(slot_port "$inactive")"

    echo "==> Menghidupkan slot ${inactive} (port ${port})"
    sudo "$SYSTEMCTL" restart "spp-api-${inactive}"

    if ! wait_ready "$port"; then
        echo "Health check slot ${inactive} gagal. Deploy dibatalkan; slot ${active} tetap melayani." >&2
        echo "Periksa log dengan: journalctl -u spp-api-${inactive} -n 100 --no-pager" >&2
        sudo "$SYSTEMCTL" stop "spp-api-${inactive}" || true
        return 1
    fi

    echo "==> Mengalihkan Nginx ke slot ${inactive}"
    if ! sudo /usr/local/bin/spp-api-switch "$inactive"; then
        echo "Gagal mengalihkan Nginx ke slot ${inactive}. Deploy dibatalkan." >&2
        sudo "$SYSTEMCTL" stop "spp-api-${inactive}" || true
        return 1
    fi

    echo "==> Menghentikan slot lama ${active}"
    sudo "$SYSTEMCTL" stop "spp-api-${active}" || true

    printf '%s\n' "$inactive" >"${ACTIVE_FILE}.next"
    mv -Tf "${ACTIVE_FILE}.next" "$ACTIVE_FILE"

    return 0
}

prune_releases() {
    local current_target count=0 release

    current_target="$(readlink -f "$CURRENT_LINK" 2>/dev/null || true)"

    while IFS= read -r release; do
        [[ -z "$release" ]] && continue

        if [[ "$(readlink -f "$release")" == "$current_target" ]]; then
            continue
        fi

        count=$((count + 1))
        if (( count > KEEP_RELEASES )); then
            rm -rf "$release"
        fi
    done < <(ls -1dt "$APP_ROOT"/releases/* 2>/dev/null || true)
}

if [[ "${1:-}" == "--rollback" ]]; then
    CURRENT_TARGET="$(readlink -f "$CURRENT_LINK" 2>/dev/null || true)"

    if [[ -z "$CURRENT_TARGET" ]]; then
        echo "Tidak ada release aktif untuk di-rollback." >&2
        exit 1
    fi

    PREVIOUS_RELEASE=""
    while IFS= read -r release; do
        [[ -z "$release" ]] && continue

        if [[ "$(readlink -f "$release")" != "$CURRENT_TARGET" ]]; then
            PREVIOUS_RELEASE="$release"
            break
        fi
    done < <(ls -1dt "$APP_ROOT"/releases/* 2>/dev/null || true)

    if [[ -z "$PREVIOUS_RELEASE" ]]; then
        echo "Tidak ada release sebelumnya untuk di-rollback." >&2
        exit 1
    fi

    echo "==> Rollback ke ${PREVIOUS_RELEASE}"
    link_release "$PREVIOUS_RELEASE"

    if ! activate_inactive_slot; then
        link_release "$CURRENT_TARGET"
        exit 1
    fi

    prune_releases
    echo "Rollback selesai: ${PREVIOUS_RELEASE}"
    exit 0
fi

if [[ -z "$REPO_URL" ]]; then
    echo "REPO_URL belum diset di $CONFIG_FILE." >&2
    exit 1
fi

RELEASE="$(date +%Y%m%d%H%M%S)"
RELEASE_DIR="$APP_ROOT/releases/$RELEASE"
PREVIOUS_TARGET="$(readlink -f "$CURRENT_LINK" 2>/dev/null || true)"

echo "==> Clone release ${RELEASE}"
git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$RELEASE_DIR"

COMMIT="$(git -C "$RELEASE_DIR" rev-parse --short HEAD)"
GO_DIR="$RELEASE_DIR/$GO_SUBDIR"

if [[ ! -d "$GO_DIR" ]]; then
    echo "Folder Go ${GO_DIR} tidak ditemukan." >&2
    exit 1
fi

echo "==> Build binary (commit ${COMMIT})"
cd "$GO_DIR"

export GOCACHE="$SHARED/go-cache"
export GOMODCACHE="$SHARED/go-mod-cache"
export CGO_ENABLED=0

"$GO_BIN" mod download
"$GO_BIN" build \
    -trimpath \
    -ldflags "-s -w -X main.version=${RELEASE} -X main.commit=${COMMIT}" \
    -o "$RELEASE_DIR/spp-api" \
    ./cmd/api

chmod 755 "$RELEASE_DIR/spp-api"

echo "==> Mengaktifkan release ${RELEASE}"
link_release "$RELEASE_DIR"

if ! activate_inactive_slot; then
    if [[ -n "$PREVIOUS_TARGET" ]]; then
        link_release "$PREVIOUS_TARGET"
    fi

    exit 1
fi

# Pastikan kedua slot hidup kembali setelah reboot. Ini hanya membuat symlink
# enable, bukan menyalakan proses yang sedang berhenti.
sudo "$SYSTEMCTL" enable spp-api-blue spp-api-green >/dev/null 2>&1 || true

prune_releases

echo "Deploy selesai: ${RELEASE_DIR}"
echo "Slot aktif: $(cat "$ACTIVE_FILE")"
