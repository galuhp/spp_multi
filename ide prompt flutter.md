# Master Prompt Pengembangan Aplikasi Flutter SPP Sekolah (Fase 1 - MVP)

File ini berisi master prompt siap pakai untuk AI (Cursor, Claude, ChatGPT, Windsurf, atau GitHub Copilot) dalam membangun aplikasi mobile Flutter yang terhubung dengan backend REST API Laravel SPP.

---

```markdown
Bertindaklah sebagai Senior Flutter Engineer. Buatkan aplikasi mobile Flutter modern untuk "Sistem Informasi Pembayaran SPP & Keuangan Siswa" (EdTech / FinTech Sekolah) Fase 1 (MVP) yang terhubung dengan backend REST API Laravel (Sanctum Authentication).

---

### 1. TEKNOLOGI & SPESIFIKASI STACK
- **Framework:** Flutter 3.x dengan Material 3 (M3).
- **State Management:** Flutter Riverpod (v2.x).
- **Networking:** Dio (dilengkapi `AuthInterceptor` untuk otomatis memasang `Bearer Token`, logging, dan penanganan HTTP 401 unauthenticated).
- **Routing:** GoRouter (declarative routing & support deep linking).
- **Secure Storage:** flutter_secure_storage (untuk menyimpan access token & sesi login).
- **Paket Pendukung:**
  - `intl` (Format mata uang Rupiah `id_ID` dan tanggal bahasa Indonesia).
  - `qr_flutter` (Menampilkan QR Code verifikasi kuitansi & QR NISN kartu pelajar).
  - `share_plus` (Membagikan tautan bukti bayar / kuitansi ke WhatsApp).
  - `shimmer` (Loading skeleton untuk performa UX yang halus).

---

### 2. STRUKTUR ARSITEKTUR FOLDER (Feature-First)
Gunakan pendekatan arsitektur bersih berbasis fitur:
```
lib/
├── core/
│   ├── constants/       # API endpoints, string constants, colors
│   ├── network/         # Dio client, interceptors, error handler
│   ├── storage/         # Secure storage service (Token & User Session)
│   ├── theme/           # AppTheme, ColorScheme, TextTheme (Plus Jakarta Sans)
│   └── utils/           # Formatters (Currency Rupiah, Indonesian Date)
├── features/
│   ├── auth/            # Login screen, auth controller, user model
│   ├── dashboard/       # Beranda, financial overview card, recent activities
│   ├── tagihan/         # 12-month SPP grid, Non-SPP bills, bill models
│   ├── riwayat/         # History list, filter, e-receipt / kuitansi viewer
│   └── profil/          # Student digital card, QR barcode, school profile
└── main.dart
```

---

### 3. FITUR UTAMA FASE 1 (MVP) & DETAIL LAYAR

#### A. Autentikasi (Auth)
- Layar login dengan input: **NISN / Username** dan **Password**.
- Checkbox "Ingat Saya" (menyimpan sesi token di secure storage).
- Auto-login saat aplikasi dibuka jika token masih valid.

#### B. Dashboard / Beranda (Home)
- **Header:** Sapaan nama siswa, kelas, jurusan, foto/avatar, dan nama sekolah.
- **Kartu Ringkasan Finansial (Financial Health Card):**
  - Nominal **Tunggakan Jatuh Tempo** (Warna merah/peringatan jika > 0, hijau jika Rp 0).
  - Akumulasi: Total Terbayar vs Total Kewajiban Tahun Ajaran berjalan.
  - Status SPP Bulan Berjalan (Contoh: `November 2026: LUNAS` / `BELUM BAYAR`).
- **Aktivitas Terakhir:** Menampilkan 2–3 transaksi pembayaran terakhir.
- Pull-to-refresh untuk menyinkronkan data terbaru.

#### C. Layar Tagihan Siswa (Bills)
Gunakan Segmented Control / TabBar:
1. **Tab SPP Bulanan (12-Month Matrix Grid):**
   - Grid 3 kolom x 4 baris mewakili bulan ajaran: Juli s.d. Juni.
   - Status Card per bulan:
     - 🟢 **Lunas:** Background hijau pastel, teks emerald, info tanggal bayar & no kuitansi.
     - 🟡 **Sebagian:** Masih ada sisa cicilan (nominal terbayar vs sisa).
     - 🔴 **Belum Lunas:** Background merah lembut (status jatuh tempo).
     - ⚪ **Belum Jatuh Tempo:** Abu-abu (bulan mendatang).
     - 🟣 **Ditanggung Pemerintah:** Bebas bayar / bantuan KIP.
2. **Tab Non-SPP (Uang Gedung, Seragam, Kegiatan):**
   - Kartu tagihan insidental dilengkapi progress bar (`Rp Terbayar` dari `Rp Total`).

#### D. Layar Riwayat & Detail Kuitansi Digital (Receipts)
- **Daftar Riwayat:** List transaksi pembayaran (tanggal, pos tagihan, nominal, metode bayar Tunai/Transfer, nama kasir).
- **Detail Kuitansi (Scalloped Ticket / E-Receipt Shape):**
  - Nomor kuitansi resmi (contoh: `SPP-20261105-0001` dengan font monospace).
  - Data sekolah, identitas siswa, rincian biaya.
  - **QR Code Resmi:** Mengarah ke URL verifikasi keaslian backend (`/bukti/{kode}/{token}`).
  - Tombol aksi: **"Bagikan ke WhatsApp"** & **"Unduh Bukti PDF"**.

#### E. Layar Kartu Pelajar Digital (ID Card)
- Kartu identitas siswa digital dengan foto, NISN, nama, kelas, jurusan.
- Menampilkan **Barcode / QR Code NISN** untuk di-scan oleh kasir/petugas TU sekolah saat pembayaran tatap muka.

---

### 4. PANDUAN DESAIN UI & DESIGN TOKENS
- **Tema Warna:**
  - Primary: Deep Indigo / Navy `#1E3A8A`
  - Success (Lunas): Emerald `#059669` (Background `#ECFDF5`)
  - Warning (Sebagian): Amber `#D97706` (Background `#FFFBEB`)
  - Danger (Tunggakan): Crimson `#E11D48` (Background `#FFF1F2`)
  - Neutral / Card: `#FFFFFF` di atas background canvas `#F8FAFC`
- **Tipografi:** Gunakan font Google Fonts `Plus Jakarta Sans` atau `Inter`. Angka nominal dan nomor transaksi wajib menggunakan tabular-nums / monospace agar mudah dibaca.
- **Handling UI:** Sediakan widget khusus untuk State `Loading` (Shimmer), `Error` (dengan tombol Refresh / Coba Lagi), dan `Empty State` (saat belum ada riwayat transaksi).

---

### 5. KONTRAK DATA / MODEL API UTAMA
Siapkan model JSON Serializable untuk data:
1. `User` & `Siswa` (id, nisn, nis, nama, kelas, jurusan, tahun_ajaran).
2. `RingkasanKeuangan` (tagihan_jatuh_tempo, total_tagihan, total_dibayar, saldo).
3. `TagihanBulanItem` (bulan, tahun, nominal, dibayar, sisa, status, no_kuitansi).
4. `TagihanNonSppItem` (id, nama_pos, kategori, nominal, dibayar, sisa, status).
5. `PembayaranItem` (id, kode_transaksi, tgl_bayar, jumlah_bayar, metode, petugas, url_verifikasi_qr).

Tolong mulai dengan membuat struktur folder dasar, konfigurasi `DioClient`, model data utama, dan implementasi layar **Login** serta **Dashboard Ringkasan Keuangan**.
```
