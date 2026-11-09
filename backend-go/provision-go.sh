#!/usr/bin/env bash
#
# Provisioning VPS Ubuntu untuk backend Go E-SPP.
#
#   sudo bash provision-go.sh
#
# Yang disiapkan:
#   - Go toolchain, Nginx, MariaDB, Redis, UFW
#   - user aplikasi tanpa akses root penuh
#   - dua slot systemd (blue/green) untuk deploy zero-downtime
#   - Nginx upstream yang dapat dipindahkan antar slot secara atomik
#
# Setelah script selesai:
#   1. Isi REPO_URL pada /var/www/spp-api/shared/deploy.env
#   2. Deploy dengan deploy-go.sh sebagai user aplikasi
#   3. Setelah release pertama, jalankan migrations/001_create_audit_logs.sql
#
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo "Jalankan provision-go.sh sebagai root." >&2
    exit 1
fi

APP_USER="${APP_USER:-spp}"
APP_ROOT="${APP_ROOT:-/var/www/spp-api}"
GO_VERSION="${GO_VERSION:-1.23.4}"
DOMAIN="${DOMAIN:-_}"
DB_NAME="${DB_NAME:-spp}"
DB_USER="${DB_USER:-spp}"
REDIS_DB="${REDIS_DB:-0}"

ARCH="$(dpkg --print-architecture)"
case "$ARCH" in
    amd64|arm64) ;;
    *)
        echo "Arsitektur $ARCH belum didukung script ini." >&2
        exit 1
        ;;
esac

echo "==> Memasang paket sistem"
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y --no-install-recommends \
    ca-certificates curl git nginx mariadb-server redis-server ufw openssl jq

echo "==> Memasang Go ${GO_VERSION}"
if ! /usr/local/go/bin/go version 2>/dev/null | grep -q "go${GO_VERSION}"; then
    curl -fsSL "https://go.dev/dl/go${GO_VERSION}.linux-${ARCH}.tar.gz" -o /tmp/go.tar.gz
    rm -rf /usr/local/go
    tar -C /usr/local -xzf /tmp/go.tar.gz
    rm -f /tmp/go.tar.gz
fi
ln -sfn /usr/local/go/bin/go /usr/local/bin/go
ln -sfn /usr/local/go/bin/gofmt /usr/local/bin/gofmt
/usr/local/go/bin/go version

echo "==> Membuat user aplikasi ${APP_USER}"
if ! id -u "$APP_USER" >/dev/null 2>&1; then
    useradd --system --create-home --shell /bin/bash "$APP_USER"
fi

install -d -o "$APP_USER" -g "$APP_USER" -m 700 "/home/${APP_USER}/.ssh"
install -d -o "$APP_USER" -g "$APP_USER" "$APP_ROOT"
install -d -o "$APP_USER" -g "$APP_USER" \
    "$APP_ROOT/releases" \
    "$APP_ROOT/shared" \
    "$APP_ROOT/shared/logs" \
    "$APP_ROOT/shared/go-cache" \
    "$APP_ROOT/shared/go-mod-cache"

echo "==> Menyiapkan MariaDB"
systemctl enable --now mariadb

DB_PASSWORD_FILE="$APP_ROOT/shared/db-password"
if [[ ! -s "$DB_PASSWORD_FILE" ]]; then
    openssl rand -hex 24 >"$DB_PASSWORD_FILE"
    chown "$APP_USER:$APP_USER" "$DB_PASSWORD_FILE"
    chmod 600 "$DB_PASSWORD_FILE"
fi

DB_PASSWORD="$(cat "$DB_PASSWORD_FILE")"
MYSQL_BIN="$(command -v mariadb || command -v mysql)"

"$MYSQL_BIN" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

echo "==> Mengaktifkan Redis"
systemctl enable --now redis-server

echo "==> Menulis environment aplikasi"
ENV_FILE="$APP_ROOT/shared/api.env"
if [[ ! -f "$ENV_FILE" ]]; then
    JWT_SECRET="$(openssl rand -base64 48 | tr -d '\n')"
    cat >"$ENV_FILE" <<EOF
APP_ENV=production
GIN_MODE=release
LOG_LEVEL=info
JWT_SECRET=${JWT_SECRET}
JWT_TTL=12h
DB_DSN=${DB_USER}:${DB_PASSWORD}@tcp(127.0.0.1:3306)/${DB_NAME}?charset=utf8mb4&parseTime=True&loc=Local
DB_AUTO_MIGRATE=false
CACHE_DRIVER=redis
REDIS_ADDR=127.0.0.1:6379
REDIS_PASSWORD=
REDIS_DB=${REDIS_DB}
APP_TIER=basic
APP_FEATURES_JSON=
TRUSTED_PROXIES=127.0.0.1
RATE_LIMIT_LOGIN=5
RATE_LIMIT_RECEIPT=30
IDEMPOTENCY_TTL=30s
SERVER_READ_HEADER_TIMEOUT=10s
SERVER_READ_TIMEOUT=30s
SERVER_WRITE_TIMEOUT=60s
SERVER_IDLE_TIMEOUT=120s
SHUTDOWN_TIMEOUT=20s
EOF
    chown "$APP_USER:$APP_USER" "$ENV_FILE"
    chmod 640 "$ENV_FILE"
fi

DEPLOY_ENV="$APP_ROOT/shared/deploy.env"
if [[ ! -f "$DEPLOY_ENV" ]]; then
    cat >"$DEPLOY_ENV" <<'EOF'
# URL repository git yang berisi folder backend-go.
REPO_URL=
BRANCH=main
KEEP_RELEASES=5
GO_SUBDIR=backend-go
EOF
    chown "$APP_USER:$APP_USER" "$DEPLOY_ENV"
    chmod 640 "$DEPLOY_ENV"
fi

ACTIVE_FILE="$APP_ROOT/shared/active-slot"
if [[ ! -f "$ACTIVE_FILE" ]]; then
    printf 'blue\n' >"$ACTIVE_FILE"
    chown "$APP_USER:$APP_USER" "$ACTIVE_FILE"
fi

echo "==> Membuat systemd service blue/green"
for slot in blue green; do
    case "$slot" in
        blue) port=8081 ;;
        green) port=8082 ;;
    esac

    cat >"/etc/systemd/system/spp-api-${slot}.service" <<EOF
[Unit]
Description=E-SPP API (${slot})
After=network-online.target mariadb.service redis-server.service
Wants=network-online.target

[Service]
Type=simple
User=${APP_USER}
Group=${APP_USER}
WorkingDirectory=${APP_ROOT}/current
EnvironmentFile=${ENV_FILE}
Environment=APP_PORT=${port}
ExecStart=${APP_ROOT}/current/spp-api
Restart=always
RestartSec=2
TimeoutStopSec=30
KillSignal=SIGTERM
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ProtectKernelTunables=true
ProtectKernelModules=true
ProtectControlGroups=true
RestrictSUIDSGID=true
ReadWritePaths=${APP_ROOT}/shared
StandardOutput=journal
StandardError=journal
LimitNOFILE=65535

[Install]
WantedBy=multi-user.target
EOF
done
systemctl daemon-reload

echo "==> Menyiapkan Nginx upstream"
install -d /etc/nginx/spp-api
cat >/etc/nginx/spp-api/upstream-blue.conf <<'EOF'
server 127.0.0.1:8081;
EOF
cat >/etc/nginx/spp-api/upstream-green.conf <<'EOF'
server 127.0.0.1:8082;
EOF
ln -sfn /etc/nginx/spp-api/upstream-blue.conf /etc/nginx/spp-api/upstream.conf

cat >/etc/nginx/sites-available/spp-api <<EOF
upstream spp_api_backend {
    include /etc/nginx/spp-api/upstream.conf;
    keepalive 32;
}

server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    access_log /var/log/nginx/spp-api.access.log;
    error_log  /var/log/nginx/spp-api.error.log;

    client_max_body_size 10m;

    location = /healthz {
        access_log off;
        proxy_pass http://spp_api_backend;
    }

    location / {
        proxy_pass http://spp_api_backend;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header Connection "";
        proxy_connect_timeout 5s;
        proxy_read_timeout 60s;
        proxy_send_timeout 60s;
    }
}
EOF

ln -sfn /etc/nginx/sites-available/spp-api /etc/nginx/sites-enabled/spp-api
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable --now nginx
systemctl reload nginx

echo "==> Membuat helper switch slot"
cat >/usr/local/bin/spp-api-switch <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

slot="${1:-}"
case "$slot" in
    blue|green) ;;
    *)
        echo "usage: spp-api-switch blue|green" >&2
        exit 2
        ;;
esac

target="/etc/nginx/spp-api/upstream-${slot}.conf"
if [[ ! -f "$target" ]]; then
    echo "File upstream $target tidak ditemukan." >&2
    exit 1
fi

previous="$(readlink -f /etc/nginx/spp-api/upstream.conf 2>/dev/null || true)"

restore_previous() {
    if [[ -n "$previous" ]]; then
        ln -sfn "$previous" /etc/nginx/spp-api/upstream.conf.new
        mv -Tf /etc/nginx/spp-api/upstream.conf.new /etc/nginx/spp-api/upstream.conf
    fi
}

ln -sfn "$target" /etc/nginx/spp-api/upstream.conf.new
mv -Tf /etc/nginx/spp-api/upstream.conf.new /etc/nginx/spp-api/upstream.conf

if ! nginx -t; then
    restore_previous
    echo "Konfigurasi Nginx tidak valid; upstream dikembalikan." >&2
    exit 1
fi

if ! systemctl reload nginx; then
    restore_previous
    nginx -t >/dev/null 2>&1 && systemctl reload nginx >/dev/null 2>&1 || true
    echo "Gagal reload Nginx; upstream dikembalikan." >&2
    exit 1
fi
EOF
chmod 755 /usr/local/bin/spp-api-switch

echo "==> Mengatur sudo terbatas untuk deploy"
SYSTEMCTL="$(command -v systemctl)"
cat >/etc/sudoers.d/spp-api <<EOF
${APP_USER} ALL=(root) NOPASSWD: \
    ${SYSTEMCTL} start spp-api-blue, \
    ${SYSTEMCTL} start spp-api-green, \
    ${SYSTEMCTL} stop spp-api-blue, \
    ${SYSTEMCTL} stop spp-api-green, \
    ${SYSTEMCTL} restart spp-api-blue, \
    ${SYSTEMCTL} restart spp-api-green, \
    ${SYSTEMCTL} is-active spp-api-blue, \
    ${SYSTEMCTL} is-active spp-api-green, \
    ${SYSTEMCTL} enable spp-api-blue spp-api-green, \
    /usr/local/bin/spp-api-switch blue, \
    /usr/local/bin/spp-api-switch green
EOF
chmod 440 /etc/sudoers.d/spp-api
visudo -cf /etc/sudoers.d/spp-api >/dev/null

echo "==> Mengaktifkan firewall"
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

cat <<EOF

Provisioning selesai.

Langkah berikutnya:
  1. Edit ${DEPLOY_ENV} dan isi REPO_URL.
  2. Deploy sebagai user ${APP_USER}:
       sudo -u ${APP_USER} bash deploy-go.sh
  3. Setelah release pertama, buat tabel audit:
       ${MYSQL_BIN} ${DB_NAME} < ${APP_ROOT}/current/backend-go/migrations/001_create_audit_logs.sql

Environment aplikasi : ${ENV_FILE}
Slot aktif           : $(cat "$ACTIVE_FILE")
Port blue/green      : 8081 / 8082
Health check         : http://127.0.0.1:8081/healthz
EOF
