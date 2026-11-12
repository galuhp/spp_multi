# Pilihan Desain UI — E-SPP Mobile

**Semua opsi memakai `shadcn_ui` (ShadApp, ShadButton, ShadCard, ShadBadge, ShadTabs, ShadSheet, ShadProgress, LucideIcons).** Opsi hanya membedakan palet, tipografi, dan komposisi layar. Bottom bar tetap `NavigationBar` Material karena shadcn_ui tidak menyediakannya; skeleton memakai `shimmer`.

Pilih satu opsi, salin blok prompt-nya ke AI (Cursor/Claude/Antigravity) di dalam folder `mobile_flutter`. Semua opsi hanya mengubah `lib/core/theme/app_theme.dart` dan widget presentasi, bukan logika.

| Opsi | Karakter | Cocok untuk |
|---|---|---|
| A. Trusted Fintech | Bersih, biru navy, kartu saldo besar | Orang tua, kesan resmi & aman |
| B. Playful Campus | Cerah, rounded, ilustrasi, gamifikasi | Siswa SMP/SMA, ramah & hangat |
| C. Minimal Ledger | Monokrom, tipografi tegas, mirip buku kas | Bendahara/wali kelas, fokus data |
| D. Glass Dark Premium | Dark mode, glassmorphism, aksen neon | Kesan modern/premium |

**Rekomendasi: A** untuk rilis awal (paling mudah dipercaya untuk urusan uang), dengan dark mode dari C.

---

## Opsi A — Trusted Fintech

```
Restyle aplikasi Flutter E-SPP (shadcn_ui, ShadThemeData) dengan gaya "Trusted Fintech".
- Warna: primary navy #1E3A8A, success #059669/#ECFDF5, warning #D97706/#FFFBEB, danger #E11D48/#FFF1F2, subsidi #7C3AED/#F5F3FF, canvas #F8FAFC.
- Font: IBM Plex Sans (teks) + IBM Plex Mono (nomor kuitansi/kode transaksi); nominal pakai tabular figures. Hindari font yang terkesan generik-AI (Inter, Space Grotesk, Sora).
- Beranda: header gradasi navy (sapaan, kelas, avatar) lalu "Financial Health Card" melayang menimpa header: tunggakan besar (merah jika >0, hijau jika Rp 0), progress terbayar vs total, chip status SPP bulan ini.
- Tagihan: ShadTabs (SPP | Non-SPP). SPP = grid 3x4 (Juli–Juni), tiap sel pastel sesuai status + ikon kecil. Non-SPP = kartu dengan progress bar.
- Riwayat: list dengan ikon metode bayar, nominal rata kanan; detail kuitansi berbentuk tiket bergerigi (scalloped) + QR verifikasi.
- Radius 16, elevasi 0 dengan border 1px #E2E8F0, bottom NavigationBar 4 tab, animasi halus 200ms.
- Wajib: skeleton shimmer, error state dengan tombol "Coba Lagi", empty state beri ilustrasi ikon.
Gunakan komponen shadcn_ui, bukan widget Material setara. Ubah hanya theme dan widget presentasi; jangan ubah controller/provider.
```

## Opsi B — Playful Campus

```
Restyle aplikasi Flutter E-SPP (shadcn_ui, ShadThemeData) bergaya "Playful Campus".
- Warna: primary teal #0EA5A4, aksen kuning #FACC15, coral #FB7185, canvas krem #FFFBF5; status tetap hijau/kuning/merah/ungu pastel.
- Font: Nunito/Poppins (judul tebal), radius 24, kartu dengan bayangan lembut berwarna.
- Beranda: sapaan "Halo, {nama}! 👋", maskot/ilustrasi sederhana, kartu "Perjalanan SPP" berupa progress 12 bulan seperti jalur/peta (titik per bulan, lunas = bintang).
- Gamifikasi ringan: badge "Bayar tepat waktu" & streak bulan lunas; confetti saat tagihan jatuh tempo menjadi Rp 0.
- Tagihan: grid bulan berupa kartu stiker; Non-SPP berupa kartu dengan progress melingkar.
- Kuitansi: kartu hangat berbentuk tiket dengan tombol besar "Kirim ke WhatsApp".
- Animasi: Lottie/implicit animations, haptic ringan saat tap.
Pastikan kontras teks memenuhi WCAG AA. Gunakan komponen shadcn_ui, bukan widget Material setara. Ubah hanya theme dan widget presentasi.
```

## Opsi C — Minimal Ledger

```
Restyle aplikasi Flutter E-SPP bergaya "Minimal Ledger" (buku kas modern).
- Palet netral: putih #FFFFFF, teks #0F172A, garis #E5E7EB, satu aksen biru #2563EB; merah/hijau hanya untuk nominal & status.
- Font: Inter untuk teks, JetBrains Mono untuk angka dan nomor kuitansi.
- Tanpa kartu berbayang: gunakan divider tipis, tabel/list rapat, header section huruf kapital kecil dengan letter-spacing.
- Beranda: satu baris ringkasan (Tunggakan | Terbayar | Total) dalam tipografi besar, lalu daftar "Aktivitas terakhir" bergaya mutasi rekening.
- Tagihan: tabel 12 bulan (Bulan | Nominal | Status | Tgl bayar), status berupa titik berwarna + teks.
- Dark mode penuh (#0B1220), kepadatan tinggi, aksesibilitas teks besar tetap rapi.
Gunakan komponen shadcn_ui, bukan widget Material setara. Ubah hanya theme dan widget presentasi.
```

## Opsi D — Glass Dark Premium

```
Restyle aplikasi Flutter E-SPP bergaya "Glass Dark Premium".
- Latar gelap #0B1020 dengan gradasi mesh ungu-biru; kartu glassmorphism (BackdropFilter blur 20, border putih 12% opasitas, radius 20).
- Aksen: cyan #22D3EE, violet #8B5CF6; status: emerald #34D399, amber #FBBF24, rose #FB7185.
- Font: Sora/Plus Jakarta Sans; nominal besar bergradasi teks.
- Beranda: kartu saldo kaca dengan donut chart terbayar vs tunggakan; kartu pelajar digital berefek holografik tipis dengan QR NISN.
- Navigasi: floating bottom bar berbentuk pil dengan indikator bergerak.
- Sediakan fallback tema terang sederhana untuk perangkat low-end (matikan blur jika perf rendah).
Gunakan komponen shadcn_ui, bukan widget Material setara. Ubah hanya theme dan widget presentasi.
```
