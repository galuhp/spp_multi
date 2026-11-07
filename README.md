# E-SPP — Sistem Pembayaran SPP Sekolah

Aplikasi web manajemen pembayaran SPP (Sumbangan Pembinaan Pendidikan) dan biaya pendidikan berbasis **Laravel 13**, **Filament v5**, **Livewire v4**, **PHP 8.5**, **SQLite**, dan **Tailwind CSS v4** (Vite). Mendukung jenjang **SD (Kelas 1–6)**, **SMP (Kelas 7–9)**, hingga **SMA/SMK (Kelas 10–12)** serta **Segmentasi Paket Basic & Pro**.

## Fitur Utama

| Modul | Deskripsi |
|---|---|
| **Data Master & Siklus Ajaran** | CRUD Tahun Ajaran, Kompetensi Keahlian (Jurusan), **Master Kelas Lintas Tahun Ajaran** (Tingkat 1–12 / I–XII), dan Siswa (dilengkapi **Tahun Masuk / Angkatan**, pencarian, filter kelas, impor Excel staging, & opsi buat akun portal otomatis) |
| **Tarif SPP & Biaya Non-SPP** | Tarif SPP bulanan per Tahun Ajaran & Tingkat, penetapan otomatis tarif baku tingkat rombel, serta biaya sekali bayar/cicilan (Uang Gedung, Seragam, Praktik, Ujian, atau kategori kustom) |
| **Kenaikan Kelas & Kelulusan** | Kenaikan kelas massal dengan **Kelas Tujuan Batch** otomatis (tunggakan dihiraukan saat naik kelas & terbawa ke TA baru, dilengkapi tombol & kotak pratinjau interaktif), pencatatan jejak `riwayat_kelas`, fitur *Rollback Batch*, serta **Kelulusan Siswa Tingkat Akhir berproteksi ketat wajib Bebas Tunggakan** |
| **Transaksi & Loket Kasir Cepat** | Loket Kasir Cepat (*POS Style*) & Form Standar, visualisasi kartu 12 bulan (Juli–Juni), pelunasan tunggakan tahun ajaran sebelumnya, multi-bulan & multi-pos dalam 1 kuitansi, cicilan, serta pencegahan pembayaran ganda |
| **Pengeluaran Operasional Sekolah** | Pencatatan transaksi belanja kas operasional sekolah (ATK, Sarpras, Gaji/Honor, Utilitas, Konsumsi, dsb.), nomor bukti otomatis `EXP-YYYYMMDD-XXXX`, unggah nota/faktur, cetak Bukti Pengeluaran Kas resmi, serta **Cetak Rekapitulasi Pengeluaran Sekolah** |
| **Pembayaran Gabungan / Kolektif** | Setoran kolektif satu kelas (modal pilih bulan SPP **dikelompokkan per Tahun Kalender / Semester** dengan banner info Tahun Ajaran, serta **otomatis menyembunyikan bulan yang sudah lunas** pada seluruh siswa terpilih) dan impor rekonsiliasi transfer bank (`.xlsx`) |
| **Kuitansi & Verifikasi QR Code** | Cetak kuitansi resmi ber-terbilang Rupiah otomatis & QR Code ber-token HMAC-SHA256 untuk verifikasi publik anti-palsu, serta pembatalan transaksi (*Void*) aman audit |
| **Tanggungan Pemerintah / Beasiswa** | Penandaan siswa penerima bantuan pemerintah (SPP penuh atau pos biaya tertentu) beserta periode berlaku & proteksi tagihan otomatis |
| **Laporan & Buku Besar Siswa** | Laporan Penerimaan (Pemasukan), **Laporan & Rekapitulasi Pengeluaran Sekolah**, Laporan Tunggakan & **Cetak Tunggakan per Kelas** (lembar distribusi Wali Kelas ber-page-break), Rekap Potensi vs Realisasi, Kartu Pembayaran Siswa (*Student Ledger*), dan Laporan Siswa Ditanggung Pemerintah |
| **Portal Mandiri Siswa** | Siswa/orang tua memantau kartu SPP 12 bulan, deteksi tunggakan tahun ajaran sebelumnya, rincian biaya non-SPP, riwayat transaksi, dan mencetak ulang kuitansi |
| **Pengguna, Role & Lisensi Paket** | Empat peran (`admin`, `petugas`, `wali_kelas`, `siswa`), Login Google OAuth, matriks izin modul (`role_permissions`), Log Aktivitas, serta sistem **Segmentasi Paket `basic` & `pro`** |

## Hak Akses

| Fitur | Admin | Petugas | Wali Kelas | Siswa |
|---|:-:|:-:|:-:|:-:|
| Dashboard | ✓ | ✓ | ✓ (ringkasan kelas) | ✓ (portal) |
| Jurusan, Kelas, Tarif SPP, Tahun Ajaran, Pengguna, Role | ✓ | ✗ | ✗ | ✗ |
| Data Siswa, Kenaikan Kelas, Kelulusan | ✓ | ✓* | ✗ | ✗ |
| Entri Pembayaran, Kasir Cepat, Pembayaran Gabungan & Kuitansi | ✓ | ✓ | ✗ | ✗ |
| Pengeluaran Operasional Sekolah & Cetak Voucher | ✓ | ✓ | ✗ | ✗ |
| Void Transaksi | ✓ | ✗ | ✗ | ✗ |
| Laporan & Rekapitulasi Umum (Pemasukan, Pengeluaran, Potensi) | ✓ | ✓ | ✗ | ✗ |
| **Laporan Tunggakan (View Only Kelas Binaan)** | ✓ | ✓ | **✓ (View Only)** | ✗ |
| Tagihan & Riwayat Pribadi (Portal Siswa) | ✗ | ✗ | ✗ | ✓ |

*\*Hak akses tiap modul dapat dikustomisasi secara dinamis melalui menu **Manajemen Role** (`role_permissions`).*

## Segmentasi Paket (Basic vs Pro)

Aplikasi mendukung pengaturan edisi paket melalui variabel `.env` (`config/spp.php` & [`App\Support\LisensiPaket`](app/Support/LisensiPaket.php)):

```env
SPP_TIER=pro                  # pilihan: basic | pro
SPP_MAX_SISWA_BASIC=200       # batas kuota siswa aktif pada paket Basic
SPP_ENABLED_FEATURES=         # opsional: daftar fitur ala-carte dipisahkan koma
SPP_DISABLED_FEATURES=        # opsional: daftar fitur yang dinonaktifkan
```

- **Basic**: Kasir SPP Standar, Cetak Kuitansi Standar, Data Master (Siswa, Kelas, Jurusan, Tahun Ajaran, Tarif SPP), Laporan Pembayaran & Tunggakan Dasar.
- **Pro**: Seluruh fitur Basic ditambah **Loket Kasir Cepat**, **Pembayaran Gabungan & Rekonsiliasi Bank**, **Impor Siswa Excel Staging**, **Kenaikan Kelas & Kelulusan Massal**, **Tanggungan Pemerintah / Beasiswa**, **Rekap Potensi Penerimaan & Kartu Pembayaran Siswa**, **Verifikasi QR Code Kuitansi**, **Portal Mandiri Siswa**, **Login Google OAuth**, dan **Manajemen Role & Log Audit**.

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

Buka <http://localhost:8000> (atau `/admin` untuk Panel Filament). Untuk mode pengembangan dengan hot reload: `composer run dev`.

## Akun Demo (hasil seeder)

| Peran | Username | Kata sandi |
|---|---|---|
| Admin | `admin` | `password` |
| Petugas | `petugas` | `password` |
| Siswa | `siswa` atau `0071234561` | `password` |

> Ganti seluruh kata sandi sebelum digunakan di lingkungan produksi. Siswa yang dibuat lewat form memakai NISN sebagai username dan kata sandi awal acak yang ditampilkan ke admin saat akun dibuat.

## Struktur Data & Siklus Tahun Ajaran

```
jurusans ─< kelas (master lintas TA, tingkat 1..12) ─< siswas (tahun_masuk) >─ spps (per TA & tingkat)
                                                         │  ├─ user_id → users (opsional)
                                                         │  └─< riwayat_kelas >─ tahun_ajarans, kelas, spps
                                                         └─< pembayarans >─ users (petugas), spps, pembayaran_gabungan
```

- **Master Kelas (`kelas`)** tidak wajib diduplikasi setiap ganti tahun ajaran (`tahun_ajaran_id` bersifat opsional/nullable). Kelas seperti `IX-A`, `X RPL 1`, `XI RPL 1`, atau `Kelas 1A` dapat dipakai lintas tahun ajaran.
- **Siswa (`siswas`)** menyimpan `tahun_masuk` (angkatan masuk, misal `2026`), `kelas_id` (kelas aktif saat ini), dan `spp_id` (tarif SPP tahun ajaran berjalan).
- **Riwayat Kelas (`riwayat_kelas`)** mencatat jejak penempatan siswa di setiap `tahun_ajaran_id` beserta `kelas_id` dan `spp_id` pada tahun ajaran tersebut (misal: Siswa A masuk `2026`, di TA `2026/2027` berada di Kelas `9`, lalu di TA `2027/2028` naik ke Kelas `10`).
- **Tagihan & Tarif (`spps`)** mengikuti **Tahun Ajaran** lintas tahun kalender (Semester Ganjil: Juli–Desember tahun awal, Semester Genap: Januari–Juni tahun berikutnya).

## Pengujian

```bash
php artisan test
```

## Dokumentasi Tambahan

- [FITUR_APLIKASI.md](FITUR_APLIKASI.md) — Katalog lengkap fitur aplikasi & segmentasi paket (materi brosur/proposal).
- [README_TABLE.md](README_TABLE.md) — Dokumentasi skema dan daftar tabel database.

## Lisensi

Proyek ini dibangun di atas framework Laravel yang berlisensi [MIT](https://opensource.org/licenses/MIT).

