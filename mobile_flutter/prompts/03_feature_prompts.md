# Prompt Lanjutan per Fitur

Jalankan berurutan di folder `mobile_flutter`. Konteks umum: Flutter 3 + shadcn_ui (ShadApp/ShadTheme; komponen Shad*, ikon LucideIcons; bottom bar NavigationBar Material, skeleton shimmer), Riverpod 2 (tanpa codegen), Dio (`unwrap()` di `core/network/dio_client.dart`), GoRouter, backend Go `/api/v1` dengan amplop `{success,message,code,data}`. Ikuti gaya kode yang sudah ada di `features/auth` dan `features/dashboard`. Selalu pakai komponen shadcn_ui bila tersedia.

> Status: bagian 1–3 sudah diimplementasikan versi awal (Tagihan, Riwayat+Kuitansi, Profil). Gunakan prompt-nya untuk menyempurnakan atau mencocokkan JSON backend.

## 1. Tagihan (`features/tagihan`)
```
Sempurnakan layar Tagihan (komponen: ShadTabs, ShadCard, ShadBadge, ShadProgress, ShadSheet): model TagihanBulanItem (bulan, tahun, nominal, dibayar, sisa, status[lunas|sebagian|belum|akan_datang|ditanggung], no_kuitansi) dan TagihanNonSppItem (id, nama_pos, kategori, nominal, dibayar, sisa, status), repository + FutureProvider dari GET /siswa/kartu-spp. UI: ShadTabs SPP/Non-SPP; SPP = grid 3x4 Juli–Juni dengan warna status; ketuk sel membuka ShadSheet detail (tanggal bayar, no kuitansi). Non-SPP = kartu dengan progress bar. Sertakan shimmer, error+retry, empty state, pull-to-refresh. Tambahkan widget test untuk pemetaan status→warna.
```
## 2. Riwayat & Kuitansi (`features/riwayat`)
```
Implementasikan Riwayat pembayaran (PembayaranItem: id, kode_transaksi, tgl_bayar, jumlah_bayar, metode, petugas, status valid|void, url_verifikasi). List dengan filter tahun ajaran. Detail kuitansi (GET /siswa/kuitansi/:kode) bergaya tiket bergerigi: nomor kuitansi monospace, rincian pos, terbilang, label VOID jika dibatalkan, QR (qr_flutter) ke URL /bukti/{kode}/{token}. Tombol "Bagikan ke WhatsApp" (share_plus) dan "Unduh PDF" (paket pdf+printing atau buka URL cetak backend).
```
## 3. Kartu Pelajar Digital (`features/profil`)
```
Implementasikan Profil: kartu pelajar digital (foto/avatar, NISN, nama, kelas, jurusan, tahun ajaran) dengan QR NISN yang dapat dipindai kasir; tombol ganti kecerahan layar saat QR tampil; info sekolah (profil sekolah) dan tombol Keluar. 
```
## 4. Keamanan & kualitas
```
Tambahkan: biometrik opsional (local_auth) untuk membuka app, penanganan 401 -> logout (sudah ada, tambahkan test), certificate/HTTPS only di release, flavor dev/prod (--dart-define), ikon & splash (flutter_launcher_icons, flutter_native_splash), unit test untuk AuthController dan Formatters, dan GitHub Actions: flutter analyze + flutter test.
```
## 5. Fase 2 (opsional, peran lain)
```
Tambahkan mode Wali Kelas (GET /wali/tunggakan, /wali/rekap-kelas/:kelas_id): daftar siswa menunggak + tombol kirim pengingat WhatsApp. Lalu mode Kasir/Petugas (POST /kasir/pembayaran dengan header Idempotency-Key, scan QR NISN, cetak kuitansi Bluetooth). Router memilih shell berdasarkan role dari JWT.
```
