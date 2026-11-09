# Master Prompt Pengembangan Backend & Middleware Golang (Gin Framework) - Sistem E-SPP Sekolah

File ini berisi **master prompt siap pakai** untuk AI (Cursor, Claude, ChatGPT, Windsurf, atau GitHub Copilot) untuk membangun arsitektur middleware, routing, dan integrasi backend API menggunakan framework Gin (Golang) untuk sistem E-SPP Sekolah.

---

```markdown
Bertindaklah sebagai Senior Golang Backend Architect. Buatkan paket middleware lengkap, modular, dan production-ready menggunakan framework Gin (`github.com/gin-gonic/gin`) untuk backend sistem "E-SPP & Manajemen Keuangan Sekolah" (porting/migrasi dari backend Laravel 11).

---

### 1. KONTEKS DOMAIN & ROLE APLIKASI
Sistem E-SPP memiliki 4 tingkatan peran (role) pengguna:
1. `admin` : Akses penuh (master data kelas/jurusan/tarif, void kuitansi, manajemen user, konfigurasi sistem).
2. `petugas` : Kasir operasional (entri pembayaran SPP/non-SPP, cetak kuitansi, input pengeluaran kas).
3. `wali_kelas` : Guru kelas (monitoring tunggakan siswa binaannya, cetak rekap per kelas).
4. `siswa` : Siswa/Wali murid (portal mandiri, kartu SPP 12 bulan, unduh kuitansi digital).

---

### 2. DAFTAR MIDDLEWARE YANG HARUS DIBUAT
Buatkan middleware dalam folder `internal/middleware/`:

1. **`AuthJWT(jwtSecret string) gin.HandlerFunc`**
   - Mengekstrak token `Bearer <token>` dari header `Authorization`.
   - Menggunakan library `github.com/golang-jwt/jwt/v5`.
   - Claims memuat: `user_id` (uint), `username` (string), `role` (string), dan opsional `siswa_id` (*uint).
   - Menyimpan seluruh klaim ke request context (`c.Set(...)`).
   - Mengembalikan respon error seragam saat token kosong, kedaluwarsa, atau tidak valid (HTTP 401).

2. **`RequireRole(allowedRoles ...string) gin.HandlerFunc`**
   - Memastikan user yang login memiliki role yang sesuai (misal: `RequireRole("admin")` atau `RequireRole("admin", "petugas")`).
   - Mengembalikan HTTP 403 Forbidden jika role tidak sesuai: `{"success": false, "message": "Anda tidak memiliki hak akses untuk rute ini.", "code": "FORBIDDEN"}`.

3. **`EnsureProTier(tierChecker LicenseChecker, featureKey ...string) gin.HandlerFunc`**
   - Mengadopsi sistem lisensi paket (AppTier: `basic` vs `pro`).
   - Memeriksa apakah fitur spesifik (seperti: `pembayaran_gabungan_excel`, `whatsapp_gateway`, `rekap_lanjutan`) dapat diakses pada lisensi yang sedang berjalan.
   - Mengembalikan HTTP 403 dengan error code `TIER_UPGRADE_REQUIRED`.

4. **`IdempotencyLock(store CacheStore, ttl time.Duration) gin.HandlerFunc`**
   - Membaca header `X-Idempotency-Key` pada endpoint transaksi POST kasir (`/api/v1/kasir/pembayaran`).
   - Menggunakan cache atomic lock (Redis / In-Memory `SetNX`) untuk mencegah transaksi kembar dan race condition nomor kuitansi.
   - Mengembalikan HTTP 409 Conflict jika request dengan key yang sama masih diproses.

5. **`RateLimiter(limit int, window time.Duration) gin.HandlerFunc`**
   - Proteksi brute-force login (`/api/v1/public/login`, limit 5 request/menit).
   - Proteksi public digital receipt scanner (`/api/v1/public/bukti/:kode/:token`).
   - Mengembalikan HTTP 429 Too Many Requests jika batas terlampaui.

6. **`AuditLogger(db *gorm.DB) gin.HandlerFunc`**
   - Mencatat log transaksi sensitif (khususnya aksi mutasi POST/PUT/DELETE pada pembayaran, void kuitansi, atau perubahan tarif).
   - Rekam: UserID, Username, Method, Path, IP Client, User-Agent, Latency (ms), dan Status Code.
   - Eksekusi asinkron (goroutine) agar tidak membebani respon kasir.

7. **`JSONRecovery() gin.HandlerFunc`**
   - Menangkap panic runtime dan mencegah crash server.
   - Mengembalikan response JSON standar HTTP 500: `{"success": false, "message": "Terjadi kesalahan internal pada server.", "code": "INTERNAL_SERVER_ERROR"}`.

---

### 3. FORMAT RESPONSE JSON TERSTANDAR
Semua endpoint dan middleware wajib mengembalikan format JSON yang konsisten:
```json
{
  "success": false,
  "message": "Pesan error bahasa Indonesia yang ramah pengguna",
  "code": "OPTIONAL_ERROR_CODE",
  "data": null
}
```

---

### 4. STRUKTUR ROUTING GIN ENGINE
Kelompokkan rute API menjadi:
- `/api/v1/public` : Tanpa auth (Login, Verifikasi QR Kuitansi Publik).
- `/api/v1/siswa` : Dilindungi `AuthJWT` & `RequireRole("siswa")`.
- `/api/v1/kasir` : Dilindungi `AuthJWT` & `RequireRole("admin", "petugas")` + `IdempotencyLock`.
- `/api/v1/admin` : Dilindungi `AuthJWT` & `RequireRole("admin")`.
- `/api/v1/pro` : Dilindungi `AuthJWT` & `EnsureProTier`.

---

### 5. PENGUJIAN & DEPLOYMENT
- Buatkan Unit Test (`net/http/httptest`) yang memverifikasi skenario sukses (200), unauthenticated (401), unauthorized (403), idempotency lock (409), dan panic recovery (500).
- Buatkan skrip deploy zero-downtime (`deploy-go.sh`), skrip provisioning VPS Ubuntu (`provision-go.sh`), serta dokumen panduan deployment (`deploy-go.md`).
```
