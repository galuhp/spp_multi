# Init Prompt — E-SPP Mobile (tempel ke AI di folder `mobile_flutter`)

> **Status eksekusi:** langkah 1–7 sudah dijalankan (platform android/ios, migrasi shadcn_ui, Tagihan, Riwayat/Kuitansi, Profil, 5 test). Header gradasi, avatar, dan "Ingat Saya" sudah selesai. Belum: pencocokan JSON dengan backend final. Prompt di bawah aman dijalankan ulang untuk menyempurnakan.

UI library: **shadcn_ui** (shadcn/ui untuk Flutter). Gaya default: **Opsi A — Trusted Fintech** berpalet shadcn. Untuk opsi lain, ganti bagian "GAYA UI" dengan blok dari `02_ui_options.md`.

```
Bertindaklah sebagai Senior Flutter Engineer. Kerjakan di folder ini (mobile_flutter), yaitu skeleton aplikasi "E-SPP Sekolah" untuk siswa/orang tua (Android & iOS).

KONTEKS
- Backend: Go/Gin, prefix /api/v1, JWT Bearer, amplop respons {success, message, code, data}.
- Endpoint siswa: POST /public/login, GET /siswa/dashboard, GET /siswa/kartu-spp, GET /siswa/kuitansi/:kode, publik GET /public/bukti/:kode/:token.
- Stack sudah terpasang: Flutter 3 + shadcn_ui ^0.59.0 (sudah membawa lucide_icons_flutter & flutter_animate), Riverpod 2 (tanpa codegen), Dio (helper unwrap() di core/network/dio_client.dart), GoRouter, flutter_secure_storage, intl id_ID, qr_flutter, share_plus, shimmer.
- Struktur feature-first: lib/core dan lib/features/{auth,dashboard,tagihan,riwayat,profil}. Auth dan Dashboard sudah jalan; Tagihan, Riwayat, Profil masih placeholder.
- Bahasa UI: Indonesia. Ikuti gaya kode yang ada di features/auth dan features/dashboard.

LANGKAH
1. Jalankan `flutter create . --project-name espp_mobile --org id.sch.espp --platforms android,ios`, lalu `flutter pub get`. Jangan menimpa file di lib/ dan pubspec.yaml; gabungkan bila perlu.
2. Jalankan `flutter analyze` dan perbaiki semua error/warning (mis. CardTheme -> CardThemeData bila versi Flutter menuntut).
3. Migrasikan UI dari Material murni ke shadcn_ui: ganti MaterialApp.router dengan ShadApp.router (theme: ShadThemeData dengan ShadSlateColorScheme / skema kustom, light & dark, textTheme Plus Jakarta Sans), pertahankan GoRouter dan lokalisasi id_ID. Ganti widget: TextFormField -> ShadInputFormField / ShadForm, FilledButton -> ShadButton, Card -> ShadCard, status -> ShadBadge, progress -> ShadProgress, bottom sheet -> ShadSheet, notifikasi -> ShadSonner/ShadToast, tab SPP/Non-SPP -> ShadTabs, ikon -> LucideIcons. Skeleton: shadcn_ui belum punya Skeleton, tetap pakai shimmer. Terapkan GAYA UI di bawah pada core/theme/app_theme.dart, Login, dan Dashboard. Bottom navigation tetap NavigationBar Material yang di-style ulang (shadcn_ui tidak menyediakannya).
4. Implementasikan layar Tagihan: grid SPP 3x4 (Juli–Juni) dengan status lunas/sebagian/belum/akan datang/ditanggung pemerintah, tab Non-SPP berisi kartu dengan progress bar, data dari GET /siswa/kartu-spp.
5. Implementasikan Riwayat + detail kuitansi (tiket bergerigi, nomor kuitansi monospace, QR ke /bukti/{kode}/{token}, tombol bagikan WhatsApp) dan Profil (kartu pelajar digital dengan QR NISN, tombol Keluar).
6. Tambahkan test: pemetaan status->warna, Formatters.rupiah, AuthController (login sukses, gagal, 401 -> signedOut) dengan Dio yang di-mock.
7. Pastikan `flutter analyze` dan `flutter test` lulus. Laporkan file yang diubah dan asumsi key JSON yang perlu dicocokkan dengan backend.

GAYA UI (Opsi A — Trusted Fintech)
- Warna: primary #1E3A8A, success #059669/#ECFDF5, warning #D97706/#FFFBEB, danger #E11D48/#FFF1F2, subsidi #7C3AED/#F5F3FF, canvas #F8FAFC.
- Font IBM Plex Sans (teks) + IBM Plex Mono (nomor kuitansi/kode transaksi); nominal pakai tabular figures. Hindari font yang terkesan generik-AI (Inter, Space Grotesk, Sora).
- Beranda: header gradasi navy (sapaan, kelas, avatar) dengan kartu "Financial Health Card" melayang: tunggakan besar (merah bila >0, hijau bila Rp 0), progress terbayar vs total, chip status SPP bulan ini.
- Radius 12–16, tanpa bayangan + border 1px #E2E8F0 (gaya shadcn), bottom NavigationBar 4 tab, animasi 200ms, dark mode.
- Wajib ada state loading (shimmer), error (tombol "Coba Lagi"), dan empty.

ATURAN
- Jangan mencampur widget Material dan shadcn untuk komponen yang sama; gunakan shadcn bila tersedia.
- Jangan ubah dependensi di luar yang diperlukan tanpa menyebutkannya.
- Jangan hardcode URL; pakai API_BASE_URL via --dart-define.
- Jangan simpan token selain di secure storage.
```
