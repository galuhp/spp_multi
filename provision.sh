#!/usr/bin/env bash
#
# Provision Ubuntu 24.04 LTS for the SPP application.
#
# Run as root, for example:
#
#   DOMAIN=spp.sekolah.sch.id \
#   REPO_URL=git@github.com:ORG/REPO.git \
#   CLOUDFLARE_TUNNEL_TOKEN=... \
#   bash provision.sh
#
# Cloudflare Tunnel is used as the public entry point. Nginx only listens on
# 127.0.0.1:80, so ports 80/443 do not need to be opened to the internet.
#
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive

DOMAIN="${DOMAIN:-spp.example.com}"
REPO_URL="${REPO_URL:-git@github.com:ORG/REPO.git}"
BRANCH="${BRANCH:-main}"
APP_ROOT="${APP_ROOT:-/var/www/spp}"
PHP_VERSION="${PHP_VERSION:-8.4}"
DB_NAME="${DB_NAME:-spp}"
DB_USER="${DB_USER:-spp}"
SSH_PORT="${SSH_PORT:-22}"
CLOUDFLARE_TUNNEL_TOKEN="${CLOUDFLARE_TUNNEL_TOKEN:-}"

if [ "$(id -u)" -ne 0 ]; then
    echo "Jalankan provision.sh sebagai root." >&2
    exit 1
fi

if [ "$DOMAIN" = "spp.example.com" ]; then
    echo "Set DOMAIN terlebih dahulu." >&2
    exit 1
fi

if [[ "$REPO_URL" == *"ORG/REPO"* ]]; then
    echo "Set REPO_URL terlebih dahulu." >&2
    exit 1
fi

if ! [[ "$DB_NAME" =~ ^[A-Za-z0-9_]+$ ]] || ! [[ "$DB_USER" =~ ^[A-Za-z0-9_]+$ ]]; then
    echo "DB_NAME dan DB_USER hanya boleh berisi huruf, angka, dan underscore." >&2
    exit 1
fi

case "$PHP_VERSION" in
    8.4|8.5) ;;
    *)
        echo "Gunakan PHP 8.4 atau 8.5. PHP 8.3 tidak memiliki Pdo\\Mysql." >&2
        exit 1
        ;;
esac

PHP_BIN="/usr/bin/php${PHP_VERSION}"
CODENAME="$(. /etc/os-release && echo "$VERSION_CODENAME")"

echo "==> Install paket dasar"

apt-get update

# config/database.php memakai Pdo\Mysql, yang tersedia sejak PHP 8.4.
# Ubuntu 24.04 hanya menyediakan PHP 8.3 secara default, jadi gunakan PPA
# Ondrej untuk PHP 8.4+.
if ! apt-cache show "php${PHP_VERSION}-fpm" >/dev/null 2>&1; then
    apt-get install -y software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt-get update
fi

# Jangan biarkan post-install script menyalakan service sebelum konfigurasi
# selesai. Ini penting untuk VPS yang IPv6-nya dimatikan: default site Nginx
# mendengarkan [::]:80 dan dapat membuat instalasi gagal.
POLICY_RC_FILE="/usr/sbin/policy-rc.d"
POLICY_RC_CREATED=0

cleanup_policy_rc() {
    if [ "$POLICY_RC_CREATED" = "1" ]; then
        rm -f "$POLICY_RC_FILE"
    fi
}

trap cleanup_policy_rc EXIT

if [ ! -e "$POLICY_RC_FILE" ]; then
    printf '#!/bin/sh\nexit 101\n' >"$POLICY_RC_FILE"
    chmod +x "$POLICY_RC_FILE"
    POLICY_RC_CREATED=1
fi

apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    unzip \
    zip \
    openssl \
    nginx \
    mariadb-server \
    mariadb-client \
    "php${PHP_VERSION}-fpm" \
    "php${PHP_VERSION}-cli" \
    "php${PHP_VERSION}-common" \
    "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" \
    "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-opcache" \
    "php${PHP_VERSION}-redis" \
    redis-server \
    systemd-timesyncd \
    ufw \
    logrotate \
    restic

cleanup_policy_rc
trap - EXIT

echo "==> Konfigurasi timezone dan NTP"

timedatectl set-timezone Asia/Jakarta || true
systemctl enable --now systemd-timesyncd || true
timedatectl set-ntp true || true

echo "==> Install Node.js 22"

if ! command -v node >/dev/null 2>&1 || ! node --version | grep -q '^v22\.'; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y nodejs
fi

echo "==> Install Composer"

if ! command -v composer >/dev/null 2>&1; then
    EXPECTED_CHECKSUM="$(curl -fsSL https://composer.github.io/installer.sig)"
    "$PHP_BIN" -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
    ACTUAL_CHECKSUM="$("$PHP_BIN" -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"

    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        rm -f /tmp/composer-setup.php
        echo "Checksum Composer tidak cocok." >&2
        exit 1
    fi

    "$PHP_BIN" /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

echo "==> Install cloudflared"

if ! command -v cloudflared >/dev/null 2>&1; then
    install -d -m 0755 /usr/share/keyrings

    curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg \
        -o /usr/share/keyrings/cloudflare-main.gpg

    chmod 0644 /usr/share/keyrings/cloudflare-main.gpg

    cat >/etc/apt/sources.list.d/cloudflared.list <<EOF
deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared ${CODENAME} main
EOF

    apt-get update
    apt-get install -y cloudflared
fi

echo "==> Swap 2 GB"

if ! swapon --show | grep -q '/swapfile'; then
    if [ ! -f /swapfile ]; then
        fallocate -l 2G /swapfile 2>/dev/null || dd if=/dev/zero of=/swapfile bs=1M count=2048 status=none
        chmod 600 /swapfile
        mkswap /swapfile
    fi

    swapon /swapfile
fi

grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >>/etc/fstab

cat >/etc/sysctl.d/99-spp.conf <<'EOF'
vm.swappiness=10
EOF

sysctl --system >/dev/null

echo "==> Buat user deploy dan direktori aplikasi"

if ! id deploy >/dev/null 2>&1; then
    adduser --disabled-password --gecos "" deploy
fi

usermod -aG www-data deploy

mkdir -p "$APP_ROOT"/{releases,shared}
mkdir -p "$APP_ROOT/shared/storage"/{app/public,framework/{cache,sessions,views},logs}

chown -R deploy:www-data "$APP_ROOT"
chmod -R 775 "$APP_ROOT/shared/storage"
chmod 750 "$APP_ROOT"

echo "==> Konfigurasi MariaDB"

DB_PASSWORD_FILE="$APP_ROOT/shared/.db-password"

if [ ! -f "$DB_PASSWORD_FILE" ]; then
    openssl rand -hex 24 >"$DB_PASSWORD_FILE"
fi

DB_PASS="$(cat "$DB_PASSWORD_FILE")"

chown deploy:www-data "$DB_PASSWORD_FILE"
chmod 600 "$DB_PASSWORD_FILE"

cat >"$APP_ROOT/shared/.my.cnf" <<EOF
[client]
host=127.0.0.1
user=${DB_USER}
password=${DB_PASS}
EOF

chown deploy:www-data "$APP_ROOT/shared/.my.cnf"
chmod 600 "$APP_ROOT/shared/.my.cnf"

cat >"$APP_ROOT/shared/deploy.env" <<EOF
APP_ROOT=${APP_ROOT}
DOMAIN=${DOMAIN}
REPO_URL=${REPO_URL}
BRANCH=${BRANCH}
PHP_VERSION=${PHP_VERSION}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
EOF

chown deploy:www-data "$APP_ROOT/shared/deploy.env"
chmod 640 "$APP_ROOT/shared/deploy.env"

cat >/etc/mysql/mariadb.conf.d/99-spp.cnf <<'EOF'
[mysqld]
bind-address = 127.0.0.1
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
innodb_buffer_pool_size = 256M
innodb_buffer_pool_instances = 1
innodb_log_file_size = 128M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 60
table_open_cache = 300
tmp_table_size = 32M
max_heap_table_size = 32M
performance_schema = OFF
skip-name-resolve = 1
EOF

systemctl enable --now mariadb
systemctl restart mariadb

for _ in $(seq 1 30); do
    if mariadb-admin ping --silent >/dev/null 2>&1; then
        break
    fi

    sleep 1
done

mariadb <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

echo "==> Konfigurasi Redis"

if [ -f /etc/redis/redis.conf ] && ! grep -q '^maxmemory 128mb' /etc/redis/redis.conf; then
    cat >>/etc/redis/redis.conf <<'EOF'

# SPP cache & session tuning
maxmemory 128mb
maxmemory-policy allkeys-lru
EOF
fi

systemctl enable --now redis-server
systemctl restart redis-server

echo "==> Konfigurasi PHP-FPM"

if [ -f "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf" ]; then
    mv "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf" \
        "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf.disabled"
fi

cat >"/etc/php/${PHP_VERSION}/fpm/pool.d/spp.conf" <<EOF
[spp]
user = www-data
group = www-data
listen = /run/php/php${PHP_VERSION}-fpm-spp.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 50M
php_admin_value[post_max_size] = 50M
php_admin_value[max_execution_time] = 300
php_admin_value[date.timezone] = Asia/Jakarta
EOF

cat >"/etc/php/${PHP_VERSION}/fpm/conf.d/99-spp.ini" <<'EOF'
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
expose_php=Off
EOF

php-fpm"${PHP_VERSION}" -t
systemctl enable --now "php${PHP_VERSION}-fpm"
systemctl restart "php${PHP_VERSION}-fpm"

echo "==> Konfigurasi Nginx"

cat >/etc/nginx/sites-available/spp <<EOF
server {
    listen 127.0.0.1:80;
    server_name ${DOMAIN};
    server_tokens off;

    root ${APP_ROOT}/current/public;
    index index.php;
    charset utf-8;
    client_max_body_size 50M;

    set_real_ip_from 127.0.0.1;
    real_ip_header CF-Connecting-IP;

    access_log /var/log/nginx/spp-access.log;
    error_log /var/log/nginx/spp-error.log;

    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 5;
    gzip_min_length 256;
    gzip_types
        text/plain
        text/css
        text/javascript
        application/javascript
        application/json
        application/xml
        image/svg+xml
        font/woff2;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;

        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm-spp.sock;
        fastcgi_param HTTPS on;
        fastcgi_param HTTP_X_FORWARDED_PROTO https;
        fastcgi_param HTTP_X_FORWARDED_FOR \$proxy_add_x_forwarded_for;
        fastcgi_read_timeout 300;
    }

    location ^~ /build/assets/ {
        expires 1y;
        add_header Cache-Control "public, max-age=31536000, immutable";
        access_log off;
        try_files \$uri =404;
    }

    location ~* \.(?:css|js|jpg|jpeg|png|gif|ico|svg|woff2?)$ {
        expires 7d;
        access_log off;
        try_files \$uri /index.php?\$query_string;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

ln -sf /etc/nginx/sites-available/spp /etc/nginx/sites-enabled/spp
rm -f /etc/nginx/sites-enabled/default

nginx -t
systemctl enable --now nginx
systemctl reload nginx

echo "==> Konfigurasi sudo untuk deploy"

cat >/etc/sudoers.d/spp-deploy <<EOF
deploy ALL=(root) NOPASSWD: /usr/bin/systemctl reload php${PHP_VERSION}-fpm, /usr/bin/systemctl restart php${PHP_VERSION}-fpm
EOF

chmod 440 /etc/sudoers.d/spp-deploy
visudo -cf /etc/sudoers.d/spp-deploy

echo "==> Konfigurasi firewall"

ufw allow "${SSH_PORT}/tcp"
ufw --force enable

echo "==> Konfigurasi direktori backup"

install -d -m 0750 /etc/spp
install -d -m 0700 /var/backups/spp

echo "==> Konfigurasi cron"

cat >/etc/cron.d/spp-scheduler <<EOF
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
MAILTO=""
* * * * * deploy cd ${APP_ROOT}/current && ${PHP_BIN} artisan schedule:run >> /dev/null 2>&1
EOF

chmod 644 /etc/cron.d/spp-scheduler

cat >/etc/cron.d/spp-backup <<EOF
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
MAILTO=""
15 2 * * * root cd ${APP_ROOT}/current && /bin/bash backup.sh >> /var/log/spp-backup.log 2>&1
EOF

chmod 644 /etc/cron.d/spp-backup

echo "==> Konfigurasi logrotate"

cat >/etc/logrotate.d/spp <<'EOF'
/var/www/spp/shared/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 deploy www-data
}
EOF

echo "==> Konfigurasi Cloudflare Tunnel"

if [ -n "$CLOUDFLARE_TUNNEL_TOKEN" ]; then
    cloudflared service install "$CLOUDFLARE_TUNNEL_TOKEN"
    systemctl enable --now cloudflared
else
    cat <<'EOF'
Cloudflare Tunnel belum dijalankan karena CLOUDFLARE_TUNNEL_TOKEN kosong.

Setelah tunnel dibuat di dashboard Cloudflare:
  1. Arahkan public hostname ke http://127.0.0.1:80
  2. Jalankan:
       cloudflared service install <TOKEN>
       systemctl enable --now cloudflared
EOF
fi

cat <<EOF

Provisioning selesai.

Langkah berikutnya:
  1. Tambahkan SSH key user deploy ke repository Git.
  2. Salin deploy.sh ke server, lalu jalankan:
       sudo -u deploy bash /path/to/deploy.sh
  3. Pastikan public hostname Cloudflare Tunnel mengarah ke:
       http://127.0.0.1:80
  4. Konfigurasi /etc/spp/backup.env untuk restic jika backup offsite dipakai.

Domain        : ${DOMAIN}
App root      : ${APP_ROOT}
PHP           : ${PHP_VERSION}
Database      : ${DB_NAME}
DB user       : ${DB_USER}
EOF
