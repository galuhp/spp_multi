# Setup Skeleton E-SPP Mobile (Android & iOS)

Folder `android/` dan `ios/` sudah dibuat. Perintah di bawah hanya perlu diulang di mesin baru atau bila platform perlu digenerate ulang:

```bash
cd mobile_flutter
flutter create . --project-name espp_mobile --org id.sch.espp --platforms android,ios
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8080/api/v1   # emulator Android
```

- iOS simulator: pakai `http://localhost:8080/api/v1`. Perangkat fisik: IP LAN server atau domain HTTPS.
- Backend: `backend-go` (Gin, JWT) prefix `/api/v1`, amplop `{success,message,code,data}`.
  Endpoint siswa: `POST /public/login`, `GET /siswa/dashboard`, `GET /siswa/kartu-spp`, `GET /siswa/kuitansi/:kode`, publik `GET /public/bukti/:kode/:token`.
- Catatan: handler Go masih bisa berupa stub (NotImplemented). Key JSON di `*_models.dart` perlu disesuaikan saat respons final.
- Android release: set `minSdkVersion 23`. iOS: `Info.plist` izinkan kamera bila memakai scanner QR.

## Struktur
UI memakai `shadcn_ui` (ShadApp). `lib/core` (constants, network/Dio+interceptor JWT, storage, theme, utils, widgets) dan `lib/features/{auth,dashboard,tagihan,riwayat,profil}`. Semua layar (Login, Beranda, Tagihan, Riwayat+Kuitansi, Profil) sudah ada versi awal.


---

## Menjalankan Aplikasi (Mode Demo vs Backend Riil)

### 1. Mode Demo / Mock Interaktif (Default)
Aplikasi dapat langsung dijalankan dan dicoba interaktif tanpa perlu menyalakan backend eksternal:
```bash
cd mobile_flutter
flutter run --dart-define=USE_MOCK=true
```
- Username: `0065432190` (atau sembarang NISN)
- Password: apa saja (masukkan `salah` untuk menguji skenario login gagal)

### 2. Terhubung ke Backend Go Riil
Jalankan backend Go terlebih dahulu:
```bash
cd backend-go
go run ./cmd/api
```
Lalu jalankan Flutter mengarah ke backend:
- **Android Emulator:** `flutter run --dart-define=USE_MOCK=false --dart-define=API_BASE_URL=http://10.0.2.2:8080/api/v1`
- **iOS Simulator:** `flutter run --dart-define=USE_MOCK=false --dart-define=API_BASE_URL=http://localhost:8080/api/v1`
- **Perangkat Fisik (LAN):** `flutter run --dart-define=USE_MOCK=false --dart-define=API_BASE_URL=http://<IP_KOMPUTER>:8080/api/v1`
