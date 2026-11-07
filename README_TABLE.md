# Daftar Tabel Database

Berikut daftar seluruh tabel database aplikasi SPP beserta kegunaannya. Rincian tabel `spps` ada di bagian bawah dokumen.

## Tabel Aplikasi

| No | Nama Tabel | Kegunaan |
|----|------------|----------|
| 1 | `users` | Akun pengguna aplikasi (admin, petugas, siswa) untuk login. Menyimpan nama, username, email, password, dan role |
| 2 | `password_reset_tokens` | Token permintaan reset password berdasarkan email |
| 3 | `sessions` | Sesi login aktif (payload sesi, IP address, user agent) |
| 4 | `jurusans` | Master jurusan/kompetensi keahlian (kode, nama, deskripsi) |
| 5 | `kelas` | Master kelas per jurusan (tingkat 1–12 / I–XII untuk SD, SMP, SMA, SMK); `tahun_ajaran_id` bersifat opsional (lintas tahun ajaran / master rombel) |
| 6 | `tahun_ajarans` | Master tahun ajaran (nama, tanggal mulai/selesai, penanda ajaran aktif) |
| 7 | `siswas` | Data induk siswa: identitas (NISN, NIS, NIK), `tahun_masuk` (angkatan), kelahiran, kontak, status, dan arsip/lulus; terhubung ke user login, kelas aktif, dan tarif SPP berjalan |
| 8 | `wali` | Data orang tua/wali per siswa (nama ayah, ibu, wali, dan nomor HP) |
| 9 | `riwayat_kelas` | Riwayat penempatan kelas (`kelas_id`) dan tarif SPP (`spp_id`) siswa per `tahun_ajaran_id`, termasuk hasil proses naik kelas/kelulusan (status, batch) |
| 10 | `spps` | Master tarif dan kategori pembayaran per tahun ajaran: SPP bulanan per tingkat (1–12 / I–XII) serta biaya non-SPP (uang gedung, seragam, praktik, ujian) dengan tingkat opsional |
| 11 | `pembayarans` | Transaksi pembayaran SPP/non-SPP per baris; beberapa baris dapat berbagi satu kode kuitansi (SPP multi-bulan atau gabungan SPP + biaya lain), termasuk relasi ke setoran gabungan dan status void |
| 12 | `pembayaran_gabungan` | Header setoran gabungan (setoran per kelas, transfer bank, catatan manual) dengan status draft → memproses → diposting → void |
| 13 | `pembayaran_gabungan_baris` | Rincian tiap baris setoran gabungan: siswa, jumlah, alokasi ke tagihan, validasi, dan peta ke transaksi `pembayarans` |
| 14 | `nomor_kuitansi_urutan` | Counter nomor kuitansi per seri dan tahun, agar nomor kuitansi berurutan dan unik |
| 15 | `import_siswa_staging` | Area penampung (staging) hasil impor Excel siswa sebelum dipromosikan ke `siswas`: data mentah per baris, status validasi, dan penanda timpa/simpan |
| 16 | `proses_kelas_batch` | Catatan batch proses kenaikan kelas dan kelulusan: tahun ajaran asal/tujuan, jumlah siswa, status, dan petugas pelaksana |
| 17 | `profil_sekolahs` | Identitas sekolah (nama, NPSN, alamat, kepala sekolah) untuk kop dokumen/laporan |
| 18 | `role_permissions` | Matriks hak akses menu per role (dashboard, transaksi SPP, portal siswa, kesiswaan, master data, pengaturan akses) |
| 19 | `log_aktivitas` | Log/audit trail aktivitas pengguna: aksi, subjek yang diubah, deskripsi, data tambahan, dan IP address |
| 20 | `pengeluaran_sekolahs` | Transaksi pengeluaran operasional dan belanja kas sekolah (nomor bukti otomatis `EXP-...`, tanggal, kategori, keperluan, nominal, penerima, metode, bukti nota, petugas, tahun ajaran) |

## Tabel Framework (bawaan Laravel)

| Nama Tabel | Kegunaan |
|------------|----------|
| `cache` | Penyimpanan cache aplikasi (driver database) |
| `cache_locks` | Lock atomik untuk cache (mencegah race condition) |
| `jobs` | Antrean pekerjaan (queue) yang menunggu dieksekusi |
| `job_batches` | Informasi batch antrean pekerjaan |
| `failed_jobs` | Pekerjaan antrean yang gagal dieksekusi |
| `migrations` | Catatan migrasi database yang sudah dijalankan |

Total saat ini: **20 tabel aplikasi + 6 tabel framework = 26 tabel**.

---

# Rincian Tabel `spps`

Tabel `spps` menyimpan **master tarif dan kategori pembayaran** sekolah, baik SPP bulanan maupun biaya non-SPP (uang gedung, seragam, praktik, ujian, dan biaya kegiatan lain).

- Model: `App\Models\Spp`
- Nama tabel: `spps`
- Primary key: `id`
- Timestamps: `created_at`, `updated_at`
- Soft delete: tidak ada

## Struktur Kolom

| Kolom | Tipe | Nullable | Default | Keterangan |
|-------|------|----------|---------|------------|
| `id` | integer, auto increment | tidak | — | Primary key |
| `kategori` | varchar(50) | tidak | `'SPP'` | Kategori/pos tarif. Contoh: `SPP`, `Uang Gedung`, `Uang Seragam`, `Biaya Praktik`, `Biaya Ujian` |
| `tipe` | varchar(30) | tidak | `'bulanan'` | Tipe pembayaran: `bulanan` atau `sekali_bayar` |
| `nama` | varchar(100) | ya | `null` | Label khusus tarif. Contoh: `DSP Angkatan 2026`, `Seragam Lengkap 4 Stel` |
| `tahun` | integer | tidak | — | Tahun referensi tarif. Pemetaan bulan memakai `tahun_ajaran` |
| `tahun_ajaran` | varchar(20) | tidak | — | Tahun ajaran dalam format `YYYY/YYYY` (contoh: `2026/2027`) |
| `tingkat` | varchar(5) | ya | `null` | Tingkat berlaku tarif: `10` (X), `11` (XI), `12` (XII). Wajib untuk SPP; opsional untuk non-SPP. `null` berarti berlaku untuk semua tingkat atau data lama |
| `nominal` | integer | tidak | — | Nominal dalam Rupiah. Untuk `bulanan`: nominal per bulan. Untuk `sekali_bayar`: nominal total tagihan |
| `keterangan` | varchar | ya | `null` | Keterangan tambahan |
| `created_at` | datetime | ya | `null` | Waktu dibuat |
| `updated_at` | datetime | ya | `null` | Waktu diperbarui |

## Index

| Nama | Kolom | Tipe |
|------|-------|------|
| `PRIMARY` | `id` | Primary key |
| `spps_kategori_tahun_tingkat_unique` | `kategori`, `tahun_ajaran`, `tingkat` | Unique |

Unique index memastikan hanya ada satu tarif per kategori, tahun ajaran, dan tingkat. Beberapa baris non-SPP dengan `tingkat = null` tetap diperbolehkan karena NULL dianggap berbeda oleh database.

## Konstanta Model

### Kategori

| Konstanta | Nilai |
|-----------|-------|
| `Spp::KATEGORI_SPP` | `SPP` |
| `Spp::KATEGORI_UANG_GEDUNG` | `Uang Gedung` |
| `Spp::KATEGORI_UANG_SERAGAM` | `Uang Seragam` |
| `Spp::KATEGORI_BIAYA_PRAKTIK` | `Biaya Praktik` |
| `Spp::KATEGORI_BIAYA_UJIAN` | `Biaya Ujian` |

Daftar preset tersedia di `Spp::DEFAULT_KATEGORI_LIST`.

### Tipe

| Konstanta | Nilai | Arti |
|-----------|-------|------|
| `Spp::TIPE_BULANAN` | `bulanan` | Iuran rutin per bulan (12 bulan per tahun ajaran) |
| `Spp::TIPE_SEKALI_BAYAR` | `sekali_bayar` | Biaya sekali bayar / dapat dicicil |

## Relasi

| Relasi | Tipe | Tabel tujuan | Foreign key |
|--------|------|--------------|-------------|
| `siswas()` | hasMany | `siswas` | `siswas.spp_id` |
| `pembayarans()` | hasMany | `pembayarans` | `pembayarans.spp_id` |

Catatan:

- `siswas.spp_id` menunjuk tarif SPP utama yang dipakai siswa.
- `pembayarans.spp_id` menunjuk tarif yang dibayar pada transaksi tersebut.
- Satu tarif dapat dipakai banyak siswa dan banyak pembayaran.

## Aturan Pemakaian

1. **Tarif SPP bulanan**
   - `kategori = 'SPP'` dan `tipe = 'bulanan'`.
   - Satu baris per `tahun_ajaran` + `tingkat`; `tingkat` wajib diisi (`10`/`11`/`12`).
   - `nominal` adalah nominal per bulan.
   - `siswas.spp_id` menunjuk tarif tingkat yang sesuai. Tarif disinkronkan saat siswa dibuat/diedit, impor siswa, dan kenaikan kelas.
   - Pemetaan tahun kalender: Juli–Desember memakai tahun mulai tahun ajaran, Januari–Juni memakai tahun berikutnya. Contoh TA `2026/2027`: Juli–Desember 2026, Januari–Juni 2027.

2. **Biaya non-SPP**
   - `kategori != 'SPP'`.
   - `tingkat` boleh `null` (berlaku semua tingkat) atau diisi jika biaya hanya berlaku untuk tingkat tertentu.
   - `tipe` umumnya `sekali_bayar`.
   - `nominal` adalah total kewajiban; pembayaran dapat penuh atau dicicil.

3. **Tahun ajaran**
   - `tahun_ajaran` = label lengkap (contoh `2026/2027`) dan menjadi acuan pemetaan tarif.
   - `tahun` hanya referensi; sistem tidak memakainya untuk menentukan bulan/tahun pembayaran.

4. **Format nominal**
   - Disimpan sebagai integer Rupiah tanpa desimal.
   - Ditampilkan lewat `formattedNominal()`, misalnya `Rp 250.000 / bulan` untuk tipe bulanan.

5. **Data lama**
   - Baris SPP lama dengan `tingkat = null` tetap disimpan agar pembayaran historis tidak rusak.
   - Baris lama tersebut tidak ditampilkan di daftar Tarif & Kategori; gunakan baris per tingkat untuk tarif baru.

## Contoh Data

| kategori | tipe | nama | tahun | tahun_ajaran | tingkat | nominal |
|----------|------|------|-------|--------------|---------|---------|
| SPP | bulanan | null | 2027 | 2026/2027 | 10 | 250000 |
| SPP | bulanan | null | 2027 | 2026/2027 | 11 | 250000 |
| SPP | bulanan | null | 2027 | 2026/2027 | 12 | 250000 |
| Uang Gedung | sekali_bayar | DSP Angkatan 2026 | 2027 | 2026/2027 | null | 2000000 |
| Uang Seragam | sekali_bayar | Seragam Lengkap 4 Stel | 2027 | 2026/2027 | null | 850000 |
| Biaya Praktik | sekali_bayar | Praktik Semester 1 | 2027 | 2026/2027 | null | 300000 |

## Catatan Terkait Pembayaran Gabungan

- Alokasi Pembayaran Gabungan memakai identitas tagihan per bulan SPP: `spp_id + bulan + tahun`, bukan tabel `tagihan` terpisah.
- Kelebihan bayar ditolak; sisa tagihan dihitung dari `spps.nominal` dikurangi pembayaran non-void.
- Tarif non-SPP yang berlaku difilter berdasarkan `tahun` atau `tahun_ajaran` milik tarif SPP siswa.

---

# Rincian Tabel `pengeluaran_sekolahs`

Tabel `pengeluaran_sekolahs` menyimpan **transaksi kas keluar dan belanja operasional sekolah** (ATK, gaji/honor, sarpras, utilitas, konsumsi, dsb.).

- Model: `App\Models\PengeluaranSekolah`
- Nama tabel: `pengeluaran_sekolahs`
- Primary key: `id`
- Timestamps: `created_at`, `updated_at`

## Struktur Kolom

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| `id` | bigint, auto increment | tidak | — | Primary key |
| `nomor_transaksi` | varchar(40) | tidak | — | Nomor bukti transaksi unik (`EXP-YYYYMMDD-XXXX`) |
| `tanggal` | date | tidak | — | Tanggal transaksi pengeluaran |
| `kategori` | varchar(50) | tidak | — | Kategori belanja (Operasional Kantor, Gaji & Honor, ATK & Percetakan, Sarpras, dsb.) |
| `keperluan` | varchar(255) | tidak | — | Uraian/keperluan belanja |
| `nominal` | integer unsigned | tidak | — | Nilai pengeluaran dalam Rupiah |
| `penerima` | varchar(100) | ya | `null` | Nama toko/vendor/pihak penerima dana |
| `metode_pembayaran` | varchar(30) | tidak | `'Tunai'` | Metode pembayaran (`Tunai`, `Transfer Bank`) |
| `catatan` | text | ya | `null` | Keterangan/catatan rincian tambahan |
| `bukti_transaksi` | varchar(255) | ya | `null` | Lokasi berkas unggahan nota/kuitansi/faktur |
| `user_id` | foreignId (users) | tidak | — | ID petugas/staf yang mencatat transaksi |
| `tahun_ajaran_id` | foreignId (tahun_ajarans) | ya | `null` | ID tahun ajaran operasional berjalan |
| `created_at` | datetime | ya | `null` | Waktu dibuat |
| `updated_at` | datetime | ya | `null` | Waktu diperbarui |

