# Panduan Deployment Backend Go E-SPP

Dokumen ini menjelaskan cara menyiapkan VPS Ubuntu, men-deploy backend Go, melakukan rollback, dan merawat layanan tanpa downtime.

---

## 1. Ringkasan Arsitektur

```text
Internet
   │
   ▼
 Nginx :80/:443
   │  upstream.conf (symlink atomik)
   ├──────────────► spp-api-blue  127.0.0.1:8081  (release lama)
   └──────────────► spp-api-green 127.0.0.1:8082  (release baru)
                          │
                          ├── MariaDB 127.0.0.1:3306
                          └── Redis   127.0.0.1:6379
```

Strategi **blue/green**:

1. Build release baru ke folder `releases/<timestamp>`.
2. Symlink `current` diarahkan ke release baru.
3. Slot yang sedang **tidak** menerima trafik di-restart dengan binary baru.
4. `/readyz` slot baru diperiksa (database + cache).
5. Nginx diarahkan ke slot baru dengan mengganti symlink upstream dan reload.
6. Slot lama dimatikan. Tidak ada request yang terputus.

Jika health check gagal, Nginx tetap mengarah ke slot lama dan symlink `current` dikembalikan.

---

## 2. Struktur Direktori di Server

```text
/var/www/spp-api/
├── current -> releases/20261109103000
├── releases/
│   ├── 20261109103000/
│   │   ├── spp-api
│   │   └── backend-go/migrations/001_create_audit_logs.sql
│   └── 20261108101000/
└── shared/
    ├── api.env          # environment aplikasi (JWT, DB, Redis, tier)
    ├── deploy.env       # REPO_URL, BRANCH, KEEP_RELEASES
    ├── db-password
    ├── active-slot      # blue | green
    ├── go-cache/
    └── go-mod-cache/
```

File system systemd dan Nginx:

```text
/etc/systemd/system/spp-api-blue.service
/etc/systemd/system/spp-api-green.service
/etc/nginx/sites-available/spp-api
/etc/nginx/spp-api/upstream-blue.conf
/etc/nginx/spp-api/upstream-green.conf
/etc/nginx/spp-api/upstream.conf -> upstream-blue.conf
/usr/local/bin/spp-api-switch
/etc/sudoers.d/spp-api
```

---

## 3. Prasyarat

- Ubuntu 22.04/24.04, akses root (sudo).
- Domain yang diarahkan ke IP VPS (opsional tetapi disarankan).
- Repository Git yang berisi folder `backend-go/`.
- Go 1.23+ (dipasang otomatis oleh `provision-go.sh`).
- MariaDB/MySQL dan Redis (dipasang otomatis).

Untuk pengembangan lokal:

```bash
cd backend-go
go mod download
go test ./...
go vet ./...
go run ./cmd/api
```

---

## 4. Konfigurasi Environment

Salin `backend-go/.env.example` menjadi `/var/www/spp-api/shared/api.env`. Provisioning melakukannya otomatis.

| Variabel | Wajib | Default | Keterangan |
|---|---|---|---|
| `APP_ENV` | tidak | `production` | Label environment pada log. |
| `GIN_MODE` | tidak | `release` | Gunakan `debug` hanya di lokal. |
| `APP_PORT` | tidak | `8080` | Jangan diset di `api.env` saat memakai unit blue/green; systemd menetapkan 8081/8082. |
| `LOG_LEVEL` | tidak | `info` | `debug`, `info`, `warn`, `error`. |
| `JWT_SECRET` | **ya** | — | Minimal 32 karakter. `openssl rand -base64 48`. |
| `JWT_TTL` | tidak | `12h` | Masa berlaku token. |
| `DB_DSN` | **ya** | — | `user:pass@tcp(host:3306)/db?charset=utf8mb4&parseTime=True&loc=Local`. |
| `DB_AUTO_MIGRATE` | tidak | `false` | Jangan aktifkan di production. |
| `CACHE_DRIVER` | tidak | `memory` | Gunakan `redis` untuk multi-instance. |
| `REDIS_ADDR` | tidak | `127.0.0.1:6379` | Alamat Redis. |
| `REDIS_PASSWORD` | tidak | kosong | Isi jika Redis memakai password. |
| `REDIS_DB` | tidak | `0` | Nomor database Redis. |
| `APP_TIER` | tidak | `basic` | `basic` atau `pro`. |
| `APP_FEATURES_JSON` | tidak | kosong | Override feature flag, mis. `{"whatsapp_gateway":"basic"}`. |
| `TRUSTED_PROXIES` | tidak | `127.0.0.1` | Daftar IP proxy dipisah koma, atau `none`. |
| `RATE_LIMIT_LOGIN` | tidak | `5` | Request login per menit per IP. |
| `RATE_LIMIT_RECEIPT` | tidak | `30` | Request scanner kuitansi per menit per IP. |
| `IDEMPOTENCY_TTL` | tidak | `30s` | TTL kunci transaksi kasir. |
| `SERVER_*_TIMEOUT` | tidak | 10s/30s/60s/120s | Timeout HTTP server. |
| `SHUTDOWN_TIMEOUT` | tidak | `20s` | Waktu drain saat SIGTERM. |

Feature flag bawaan mengikuti `config/spp.php` Laravel:

| Fitur | Tier minimum |
|---|---|
| `pembayaran_kasir` | basic |
| `pembayaran_kolektif` | basic |
| `tanggungan_pemerintah` | basic |
| `laporan_standar` | basic |
| `kenaikan_dan_kelulusan` | basic |
| `pembayaran_gabungan_excel` | pro |
| `whatsapp_gateway` | pro |
| `rekap_lanjutan` | pro |

Fitur yang tidak terdaftar dianggap khusus Pro, sama seperti `LisensiPaket::hasFeature()` di Laravel.

> Setelah mengubah `api.env`, jalankan deploy ulang atau `systemctl restart` slot aktif. Perubahan JWT secret langsung membatalkan seluruh token lama.

---

## 5. Provisioning VPS

```bash
sudo bash backend-go/provision-go.sh
```

Variabel opsional:

```bash
sudo DOMAIN=espp.sekolah.sch.id APP_USER=spp bash backend-go/provision-go.sh
```

Yang dilakukan script:

- Memasang Nginx, MariaDB, Redis, UFW, dan Go.
- Membuat user sistem `spp`.
- Membuat database `spp` dan user database dengan password acak.
- Membuat `/var/www/spp-api/shared/api.env` dengan JWT secret acak.
- Membuat dua unit systemd blue/green.
- Membuat konfigurasi Nginx dengan upstream yang bisa dipindah.
- Membuat helper `spp-api-switch`.
- Memberi sudo hanya untuk start/stop/restart slot dan switch Nginx.
- Mengaktifkan UFW (OpenSSH + Nginx).

Setelah provisioning, isi repository:

```bash
sudo -u spp nano /var/www/spp-api/shared/deploy.env
```

```dotenv
REPO_URL=git@github.com:sekolah/espp.git
BRANCH=main
KEEP_RELEASES=5
GO_SUBDIR=backend-go
```

Gunakan deploy key read-only untuk repository privat. Untuk clone via SSH, tambahkan host key GitHub/GitLab sekali agar `git clone` non-interaktif:

```bash
sudo -u spp ssh-keyscan github.com >> /home/spp/.ssh/known_hosts
```

### 5.1 Membuat Tabel Audit

Jalankan migrasi sebelum trafik kasir masuk:

```bash
mysql -u spp -p spp < backend-go/migrations/001_create_audit_logs.sql
```

Atau setelah release pertama:

```bash
mysql -u spp -p spp < /var/www/spp-api/current/backend-go/migrations/001_create_audit_logs.sql
```

Migration membuat foreign key ke `users(id)`. Jika instalasi baru belum memiliki tabel `users`, buat tabel tersebut lebih dulu atau hapus blok `CONSTRAINT fk_audit_logs_user` pada migration.

Audit logger bersifat **best-effort**: kegagalan menulis audit tidak menggagalkan transaksi kasir, tetapi tercatat sebagai error di journal. Pantau:

```bash
journalctl -u spp-api-blue -u spp-api-green -f | grep -i audit
```

---

## 6. Deploy

```bash
sudo -u spp bash /path/ke/repo/backend-go/deploy-go.sh
```

Atau dari release yang sudah ada:

```bash
sudo -u spp bash /var/www/spp-api/current/backend-go/deploy-go.sh
```

Alur yang dijalankan:

1. `git clone --depth 1` ke `releases/<timestamp>`.
2. `go mod download` dan `go build` dengan `CGO_ENABLED=0`.
3. Binary ditempatkan di `releases/<timestamp>/spp-api`.
4. Symlink `current` diganti secara atomik (`ln` + `mv -Tf`).
5. Slot non-aktif di-restart.
6. `/readyz` dipolling maksimal 30 detik.
7. Nginx diarahkan ke slot baru.
8. Slot lama dihentikan.
9. Release lama dipangkas (menyisakan `KEEP_RELEASES`).

Jika build atau health check gagal:

- Slot lama tetap menerima trafik.
- Slot baru dihentikan.
- Symlink `current` dikembalikan ke release sebelumnya.

### 6.1 Verifikasi

```bash
systemctl status spp-api-blue spp-api-green --no-pager
curl -fsS http://127.0.0.1:8081/healthz
curl -fsS http://127.0.0.1:8082/healthz
curl -fsS https://espp.sekolah.sch.id/readyz
```

`/healthz` hanya memeriksa proses. `/readyz` memeriksa database dan cache.

---

## 7. Rollback

```bash
sudo -u spp bash /var/www/spp-api/current/backend-go/deploy-go.sh --rollback
```

Rollback memilih release terbaru selain release aktif, mengarahkan `current` ke release tersebut, menghidupkan slot non-aktif, memeriksa `/readyz`, lalu memindahkan Nginx.

Rollback tidak menjalankan migrasi database. Pastikan perubahan skema bersifat **backward compatible** (tambah kolom/tabel, hindari rename/drop langsung).

---

## 8. Migrasi Database yang Aman

Untuk zero-downtime, urutan yang disarankan:

1. Deploy kode yang kompatibel dengan skema lama **dan** baru (expand).
2. Jalankan migration additive (tambah kolom nullable, tabel baru, index).
3. Deploy kode yang memakai skema baru.
4. Setelah stabil, hapus kolom lama pada rilis berikutnya (contract).

Jangan menjalankan `ALTER TABLE` berat saat jam kasir sibuk. Untuk tabel besar, gunakan `pt-online-schema-change` atau `gh-ost`.

---

## 9. Testing

```bash
cd backend-go

# Seluruh unit test middleware + router
go test ./... -count=1

# Format dan analisis statis
gofmt -w .
go vet ./...
```

Cakupan test:

- `AuthJWT`: token valid, kosong, rusak, tanda tangan salah, kedaluwarsa (401).
- `RequireRole`: role cocok, role salah (403), tanpa konteks auth (401).
- `EnsureProTier`: Basic/Pro, feature matrix, checker error (500).
- `IdempotencyLock`: key hilang (400), key tidak valid (400), request paralel (409), release setelah selesai, store error (503), GET dikecualikan.
- `RateLimiter`: batas tercapai (429), isolasi per IP, reset jendela.
- `AuditLogger`: field audit lengkap, GET tidak diaudit, panic tercatat 500.
- `JSONRecovery`: panic menjadi 500, response yang sudah terkirim tidak ditimpa.
- Router: proteksi seluruh grup rute dan rate limit login.

Membuat token untuk pengujian lokal:

```bash
export JWT_SECRET='rahasia-lokal-minimal-32-karakter-123456'
go run ./cmd/token -user 1 -username admin -role admin
```

---

## 10. Operasional

### Melihat log

```bash
journalctl -u spp-api-blue -u spp-api-green -f
journalctl -u spp-api-green -n 200 --no-pager
```

### Status slot

```bash
cat /var/www/spp-api/shared/active-slot
cat /etc/nginx/spp-api/upstream.conf
```

### Restart manual slot aktif

```bash
sudo systemctl restart spp-api-green
```

### Mengubah tier lisensi

```bash
sudo -u spp nano /var/www/spp-api/shared/api.env
# APP_TIER=pro
sudo systemctl restart spp-api-$(cat /var/www/spp-api/shared/active-slot)
```

Tidak perlu rebuild karena tier dibaca saat startup.

### Rotasi JWT secret

```bash
sudo -u spp nano /var/www/spp-api/shared/api.env
# JWT_SECRET=$(openssl rand -base64 48)
sudo systemctl restart spp-api-$(cat /var/www/spp-api/shared/active-slot)
```

Semua pengguna harus login ulang.

### Backup

Backup harian database dan file environment:

```bash
mysqldump --single-transaction spp | gzip > /var/backups/spp-$(date +%F).sql.gz
tar -czf /var/backups/spp-api-env-$(date +%F).tar.gz -C /var/www/spp-api/shared api.env
```

---

## 11. Troubleshooting

| Gejala | Penyebab umum | Tindakan |
|---|---|---|
| Deploy gagal di health check | DB/Redis tidak siap, migration belum jalan | `journalctl -u spp-api-<slot> -n 100`; cek `DB_DSN`, Redis, dan tabel audit. |
| Nginx 502 | Slot tidak berjalan atau port salah | `systemctl status spp-api-blue/green`; cek `ss -lntp`. |
| Kedua slot berebut port 8080 | `APP_PORT` masih diset di `api.env` | Hapus baris `APP_PORT` dari `api.env`, restart slot aktif. |
| `JWT_SECRET wajib diisi minimal 32 karakter` | Secret kosong/terlalu pendek | Isi `api.env`, restart slot. |
| Semua request 401 | Secret berbeda antar instance atau token lama | Samakan `JWT_SECRET` pada kedua slot; minta login ulang. |
| 409 saat pembayaran | Request paralel memakai `X-Idempotency-Key` sama | Tunggu request pertama selesai; kirim key unik per transaksi. |
| 429 saat login | Rate limit 5/menit per IP | Tunggu jendela berikutnya; cek `X-RateLimit-*` dan `Retry-After`. |
| Audit log tidak muncul | Tabel belum dibuat atau antrean penuh | Jalankan migration; cek error `audit` di journal. |
| `go: command not found` saat deploy | Go tidak terpasang | Jalankan `provision-go.sh` atau set `GO_BIN`. |
| Permission denied saat switch Nginx | Sudoers tidak sesuai path systemctl | Periksa `/etc/sudoers.d/spp-api`. |

Perintah diagnosa cepat:

```bash
curl -i http://127.0.0.1:8081/healthz
curl -i http://127.0.0.1:8082/readyz
ss -lntp | grep -E '8081|8082'
nginx -t
sudo -u spp bash -n /var/www/spp-api/current/backend-go/deploy-go.sh
```

---

## 12. Catatan Keamanan dan Batasan

1. **Rate limiter in-memory** hanya akurat per proses. Deployment blue/green memiliki dua proses, sehingga batas efektif dapat berlipat selama masa transisi. Untuk batas global, gunakan Redis-based limiter di depan aplikasi (mis. Nginx `limit_req`) atau modifikasi middleware agar memakai Redis.
2. **IdempotencyLock** mencegah transaksi paralel, bukan mengganti idempotensi bisnis. Setelah handler selesai, kunci dilepas. Untuk replay response yang persis sama, simpan hasil transaksi berdasarkan `X-Idempotency-Key` di database.
3. **Audit logger** menulis asinkron dengan antrean terbatas (1024 entri). Saat antrean penuh, entri dibuang dan warning dicatat. Jangan jadikan audit sebagai satu-satunya bukti kepatuhan; gunakan juga log transaksi database.
4. **JWT** memakai HS256. Jangan membagikan secret ke pihak lain. Untuk kebutuhan multi-tenant, pertimbangkan RS256 dengan key rotation.
5. **Trusted proxies**: default hanya `127.0.0.1`. Jangan isi `*` karena `X-Forwarded-For` dapat dipalsukan.
6. **TLS**: provisioning hanya menyiapkan HTTP. Pasang sertifikat dengan Certbot:
   ```bash
   apt-get install -y certbot python3-certbot-nginx
   certbot --nginx -d espp.sekolah.sch.id
   ```
7. **Database**: user aplikasi hanya boleh mengakses database `spp`. Jangan buka port 3306/6379 ke internet.
8. **`DB_AUTO_MIGRATE`** hanya untuk lokal. Di production gunakan migration versioned.
