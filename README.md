# E-SPP — Sistem Pembayaran SPP Sekolah

Aplikasi web manajemen pembayaran SPP (Sumbangan Pembinaan Pendidikan) berbasis **Laravel 13**, **PHP 8.5**, **SQLite**, dan **Tailwind CSS v4** (Vite).

## Fitur

| Modul | Deskripsi |
|---|---|
| **Data Master** | CRUD Jurusan (Kompetensi Keahlian), Kelas, dan Siswa (pencarian & filter kelas, opsi buat akun portal otomatis) |
| **Tarif SPP** | Tarif per tahun ajaran dan nominal per bulan |
| **Pengguna & Hak Akses** | Tiga peran: Admin, Petugas, Siswa (middleware `role`) |
| **Transaksi Pembayaran** | Bayar satu atau beberapa bulan sekaligus, kode transaksi otomatis `SPP-YYYYMMDD-XXXX`, pencegahan pembayaran ganda |
| **Kuitansi** | Halaman rincian dan cetak bukti bayar |
| **Laporan** | Rekap berdasarkan periode, kelas, dan metode bayar, dengan versi cetak (kop surat & tanda tangan) |
| **Portal Siswa** | Siswa melihat tagihan, riwayat, dan mencetak kuitansi sendiri |

## Hak Akses

| Fitur | Admin | Petugas | Siswa |
|---|:-:|:-:|:-:|
| Dashboard | ✓ | ✓ | ✓ (portal) |
| Jurusan, Kelas, Tarif SPP, Pengguna | ✓ | ✗ | ✗ |
| Data Siswa | ✓ | ✓ | ✗ |
| Entri pembayaran & kuitansi | ✓ | ✓ | ✗ |
| Hapus transaksi | ✓ | ✗ | ✗ |
| Laporan | ✓ | ✓ | ✗ |
| Tagihan & riwayat pribadi | ✗ | ✗ | ✓ |

## Persyaratan

- PHP >= 8.3 (disarankan 8.5), ekstensi `sqlite3`
- Composer
- Node.js >= 20 dan npm

## Instalasi

```bash
composer install
cp .env.example .env        # lewati jika .env sudah ada
php artisan key:generate
touch database/database.sqlite   # Windows: New-Item database/database.sqlite
php artisan migrate --seed
npm install
npm run build
```

Atau gunakan skrip bawaan: `composer run setup`.

## Menjalankan

```bash
php artisan serve
```

Buka <http://localhost:8000>. Untuk mode pengembangan dengan hot reload: `composer run dev`.

## Akun Demo (hasil seeder)

| Peran | Username | Kata sandi |
|---|---|---|
| Admin | `admin` | `password` |
| Petugas | `petugas` | `password` |
| Siswa | `siswa` atau `0071234561` | `password` |

> Ganti seluruh kata sandi sebelum digunakan di lingkungan produksi. Siswa yang dibuat lewat form memakai NISN sebagai username dan kata sandi awal.

## Struktur Data

```
jurusans ─< kelas ─< siswas >─ spps
                       │  └─ user_id → users (opsional)
                       └─< pembayarans >─ users (petugas), spps
```

- `pembayarans` memiliki unique constraint `(siswa_id, bulan_dibayar, tahun_dibayar)`.
- Role disimpan di `users.role` melalui enum [`App\Enums\UserRole`](app/Enums/UserRole.php).

## Struktur Utama

```
app/Enums/UserRole.php
app/Http/Controllers/   Auth, Dashboard, Jurusan, Kelas, Siswa, Spp, User,
                        Pembayaran, Laporan, SiswaPortal
app/Http/Middleware/CheckRole.php
app/Models/             User, Jurusan, Kelas, Siswa, Spp, Pembayaran
database/migrations/    Skema tabel
database/seeders/       Data contoh
resources/views/        layouts, auth, dashboard, jurusan, kelas, siswa, spp,
                        user, pembayaran, laporan, siswa_portal
routes/web.php          Rute dan pengelompokan role
tests/Feature/          AuthTest, SppSystemTest
```

## Pengujian

```bash
php artisan test
```

## Catatan Kustomisasi

Nama sekolah, alamat, dan pejabat penandatangan pada kuitansi dan laporan masih berupa contoh. Ubah di `resources/views/pembayaran/` (`show`, `cetak`) dan `resources/views/laporan/cetak.blade.php`.

## Lisensi

Proyek ini dibangun di atas framework Laravel yang berlisensi [MIT](https://opensource.org/licenses/MIT).
