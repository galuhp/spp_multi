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
| `tahun` | integer | tidak | — | Tahun mulai tahun ajaran (contoh: `2026` untuk TA `2026/2027`) |
| `tahun_ajaran` | varchar(20) | tidak | — | Tahun ajaran dalam format `YYYY/YYYY` (contoh: `2026/2027`) |
| `nominal` | integer | tidak | — | Nominal dalam Rupiah. Untuk `bulanan`: nominal per bulan. Untuk `sekali_bayar`: nominal total tagihan |
| `keterangan` | varchar | ya | `null` | Keterangan tambahan |
| `created_at` | datetime | ya | `null` | Waktu dibuat |
| `updated_at` | datetime | ya | `null` | Waktu diperbarui |

## Index

| Nama | Kolom | Tipe |
|------|-------|------|
| `PRIMARY` | `id` | Primary key |

Tidak ada unique index pada tabel ini. Kombinasi kategori/tahun ajaran dapat memiliki beberapa tarif (misalnya beberapa pos non-SPP pada tahun ajaran yang sama).

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
   - `nominal` adalah nominal per bulan.
   - Pembayaran dibuat per bulan (`pembayarans.bulan_dibayar`) dengan `tahun_dibayar` mengikuti `spps.tahun`.

2. **Biaya non-SPP**
   - `kategori != 'SPP'`.
   - `tipe` umumnya `sekali_bayar`.
   - `nominal` adalah total kewajiban; pembayaran dapat penuh atau dicicil.

3. **Tahun ajaran**
   - `tahun` = tahun mulai (contoh `2026`).
   - `tahun_ajaran` = label lengkap (contoh `2026/2027`).
   - Keduanya dipakai untuk memfilter tarif yang berlaku pada tahun ajaran siswa.

4. **Format nominal**
   - Disimpan sebagai integer Rupiah tanpa desimal.
   - Ditampilkan lewat `formattedNominal()`, misalnya `Rp 250.000 / bulan` untuk tipe bulanan.

## Contoh Data

| kategori | tipe | nama | tahun | tahun_ajaran | nominal |
|----------|------|------|-------|--------------|---------|
| SPP | bulanan | null | 2026 | 2026/2027 | 250000 |
| Uang Gedung | sekali_bayar | DSP Angkatan 2026 | 2026 | 2026/2027 | 2000000 |
| Uang Seragam | sekali_bayar | Seragam Lengkap 4 Stel | 2026 | 2026/2027 | 850000 |
| Biaya Praktik | sekali_bayar | Praktik Semester 1 | 2026 | 2026/2027 | 300000 |

## Catatan Terkait Pembayaran Massal

- Alokasi Pembayaran Massal memakai identitas tagihan per bulan SPP: `spp_id + bulan + tahun`, bukan tabel `tagihan` terpisah.
- Kelebihan bayar ditolak; sisa tagihan dihitung dari `spps.nominal` dikurangi pembayaran non-void.
- Tarif non-SPP yang berlaku difilter berdasarkan `tahun` atau `tahun_ajaran` milik tarif SPP siswa.
