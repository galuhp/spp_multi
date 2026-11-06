# Katalog Fitur Aplikasi E-SPP Sekolah (Materi Brosur)

Dokumen ini merangkum seluruh fitur utama **Aplikasi E-SPP Sekolah** dengan bahasa yang siap dikutip untuk pembuatan **brosur, proposal, maupun presentasi profil produk**.

---

## Ringkasan Nilai Jual Utama (Headline Brosur)

- **Kasir Cepat & Fleksibel** — Entri pembayaran per siswa maupun kolektif satu kelas (SPP bulanan, Uang Gedung, Seragam, Praktik, Ujian, atau gabungan sekaligus).
- **Kuitansi Digital Ber-QR Code Anti-Palsu** — Setiap kuitansi dilengkapi QR Code ber-token kriptografis (HMAC-SHA256) yang bisa dipindai orang tua untuk verifikasi keaslian secara instan tanpa perlu login.
- **Transparan bagi Siswa & Orang Tua** — Portal mandiri untuk memantau kartu tagihan 12 bulan, sisa biaya non-SPP, serta mengunduh ulang kuitansi kapan saja.
- **Akuntabel & Siap Audit** — Nomor kuitansi berurutan otomatis tanpa celah, sistem *Void* (pembatalan tercatat dengan alasan tanpa menghapus jejak nomor kuitansi), dan Log Aktivitas lengkap.
- **Dukungan Beasiswa / Tanggungan Pemerintah** — Penandaan siswa penerima bantuan pemerintah baik untuk SPP penuh maupun pos biaya tertentu lengkap dengan rekap laporannya.

---

## 1. Modul Transaksi & Loket Kasir SPP

1. **Entri Pembayaran Kasir Cepat (2 Mode Loket)**
   - **Form Standar & Loket Kasir Cepat (*POS Style*)**: Cari siswa berdasarkan Nama, NISN, atau NIS dengan filter tingkat kelas (X, XI, XII).
   - **Visualisasi Kartu 12 Bulan (Juli–Juni)**: Langsung menampilkan status tiap bulan (*Tunggakan*, *Bulan Berjalan*, *Akan Datang*, *Lunas*, atau *Ditanggung Pemerintah*).
   - **Pembayaran Multi-Bulan & Multi-Pos Sekaligus**: Satu kali checkout dapat melunasi beberapa bulan SPP sekaligus ditambah pos biaya lain (Uang Gedung, Seragam, Praktik, Ujian) dalam **satu nomor kuitansi**.
   - **Pembayaran Bertahap / Cicilan**: Mendukung pembayaran sebagian (*partial payment*) untuk tagihan yang dicicil; sistem otomatis menghitung sisa tagihan.
   - **Multi-Metode Pembayaran**: Tunai, Transfer Bank, dan QRIS.

2. **Pembayaran Gabungan / Kolektif Massal**
   - **Setoran Kolektif per Kelas**: Pilih kelas (berdasarkan Kompetensi Keahlian atau Tingkat), centang banyak siswa sekaligus, lalu pilih bulan SPP **dan/atau** tarif biaya lain (Non-SPP) dalam satu jendela.
   - **Impor Rekonsiliasi Transfer Bank (Excel)**: Unggah mutasi/daftar transfer bank via template `.xlsx`; sistem otomatis mencocokkan NIS/NISN dan mengalokasikan ke tagihan tertua (*FIFO*) atau sesuai alokasi manual.
   - **Kontrol Rekonsiliasi Draft → Posting**: Validasi otomatis apakah total uang diterima sama persis dengan total rincian baris siswa sebelum diposting, lengkap dengan unduhan laporan baris error (`.csv`).
   - **Cetak Kuitansi Massal & Lembar Rekap Setoran**: Cetak seluruh kuitansi siswa dalam satu batch sekaligus beserta lembar rekapitulasi kolektifnya.

3. **Kuitansi Resmi, Terbilang Otomatis & Verifikasi QR Code**
   - **Format Kuitansi Siap Cetak**: Lengkap dengan kop sekolah dinamis, rincian pos pembayaran, nominal angka & **kalimat terbilang Rupiah otomatis**, serta tanda tangan petugas.
   - **QR Code Verifikasi Publik**: Memindai QR Code pada kuitansi fisik/PDF akan membuka halaman **Bukti Pembayaran Digital** resmi yang menampilkan status keaslian transaksi (*Valid* atau *Void*).
   - **Penomoran Kuitansi Otomatis & Anti-Ganda**: Dikunci secara atomik (*atomic lock*) agar tidak pernah terjadi nomor kuitansi kembar meskipun diakses beberapa kasir bersamaan.

4. **Pembatalan Transaksi (*Void*) yang Aman Audit**
   - Transaksi salah input tidak dihapus dari database melainkan di-*void* oleh Administrator dengan **wajib mengisi alasan pembatalan**.
   - Nomor kuitansi tetap tersimpan dalam urutan dan tercatat di riwayat audit serta Kartu Pembayaran Siswa.

---

## 2. Modul Tanggungan Pemerintah / Beasiswa

1. **Penandaan Cakupan Fleksibel per Siswa**
   - Dapat menandai siswa sebagai penerima bantuan untuk **SPP Bulanan penuh** maupun **Pos Biaya tertentu** (mis. bebas Uang Gedung atau Biaya Ujian saja).
2. **Periode Berlaku & Pencabutan Tercatat**
   - Mendukung tanggal mulai ditanggung serta fitur pencabutan status lengkap dengan alasan pencabutan.
3. **Proteksi Tagihan Otomatis**
   - Bulan SPP atau pos biaya yang berstatus *Ditanggung Pemerintah* otomatis terkunci di kasir sehingga tidak tertagih ganda ke siswa.

---

## 3. Modul Laporan & Rekapitulasi Keuangan

Seluruh laporan dilengkapi tampilan pratinjau interaktif dan **mode cetak dokumen resmi / PDF** ber-kop sekolah & tanda tangan Kepala Sekolah:

1. **Laporan & Rekapitulasi Penerimaan Pembayaran**
   - Filter berdasarkan rentang tanggal, rombel kelas, kategori pos biaya, dan metode pembayaran (Tunai/Transfer/QRIS).
2. **Laporan Tunggakan Siswa**
   - Matriks keterlambatan pembayaran per siswa dan ringkasan per kelas/kategori non-SPP, dengan filter tingkat, kelas, status tunggakan, serta pengurutan berdasarkan nominal tunggakan terbesar.
3. **Rekapitulasi Potensi vs Realisasi Penerimaan**
   - Analisis target penerimaan sekolah dibandingkan uang masuk aktual dan sisa piutang, dapat dikelompokkan per **Rombel Kelas**, **Kompetensi Keahlian (Jurusan)**, atau **Tingkat Kelas**.
4. **Kartu Pembayaran Siswa (*Student Ledger*)**
   - Buku besar kronologis per siswa dalam satu tahun ajaran yang memuat kolom Debit (tagihan jatuh tempo), Kredit (pembayaran masuk), Saldo berjalan, serta daftar transaksi yang pernah di-*void*.
5. **Laporan Siswa Ditanggung Pemerintah**
   - Daftar siswa penerima bantuan aktif maupun yang sudah dicabut beserta total nilai nominal tanggungannya.

---

## 4. Modul Portal Mandiri Siswa & Orang Tua

1. **Dashboard & Kartu Status SPP 12 Bulan**
   - Siswa/orang tua dapat login menggunakan **NISN** untuk melihat status pelunasan setiap bulan dari Juli s.d. Juni secara visual.
2. **Rincian Tagihan & Biaya Non-SPP**
   - Menampilkan total yang sudah dibayar, sisa kewajiban SPP maupun pos non-SPP, serta penanda bulan yang ditanggung pemerintah.
3. **Riwayat Transaksi & Cetak Ulang Kuitansi Mandiri**
   - Unduh atau cetak ulang kuitansi pembayaran resmi kapan saja dari HP atau komputer rumah.

---

## 5. Modul Kesiswaan & Siklus Tahun Ajaran

1. **Data Induk Siswa & Wali**
   - Pencatatan biodata lengkap (NISN, NIS, NIK, TTL, Agama, Alamat, Tanggal Masuk) beserta data Ayah, Ibu, dan Nomor HP Wali.
2. **Impor Siswa Massal via Excel dengan Area *Staging***
   - Unggah ribuan data siswa lewat template Excel resmi.
   - Dilengkapi pratinjau validasi per baris (*Valid* / *Error* beserta alasan detailnya), opsi timpa data lama, dan ringkasan hasil impor sebelum benar-benar disimpan ke database.
3. **Kenaikan Kelas Massal & Riwayat Kelas**
   - Memindahkan siswa antartingkat (X → XI → XII) pada pergantian tahun ajaran dengan status *Naik Kelas* atau *Tinggal Kelas*, otomatis menyinkronkan tarif SPP tingkat baru, dan menyimpan jejak `riwayat_kelas` agar laporan tahun ajaran lama tetap akurat.
   - Dilengkapi fitur **Batalkan Batch (*Rollback*)** apabila belum ada pembayaran di tahun ajaran baru.
4. **Kelulusan & Pengarsipan Siswa Kelas Akhir**
   - Meluluskan siswa tingkat akhir secara massal lengkap dengan pengecekan sisa tunggakan, memindahkan ke status arsip, serta fitur koreksi (keluarkan dari arsip dengan alasan tercatat).

---

## 6. Modul Master Data & Pengaturan Sekolah

1. **Master Tahun Ajaran** — Pengelolaan periode tahun ajaran aktif dan historis.
2. **Master Kompetensi Keahlian (Jurusan) & Rombel Kelas** — Pengaturan kode jurusan, tingkat kelas (X/XI/XII), dan kapasitas siswa per rombel.
3. **Master Tarif SPP per Tingkat & Pos Biaya Non-SPP** — Mendukung tarif SPP berbeda untuk tiap tingkat kelas (X, XI, XII) serta pos biaya sekali bayar/cicilan (Uang Gedung, Seragam, Praktik, Ujian, atau kategori kustom).
4. **Profil & Identitas Sekolah Dinamis** — Pengaturan nama aplikasi, logo sekolah, nama instansi/pemerintah daerah, alamat, kontak, hingga nama & NIP Kepala Sekolah yang otomatis tampil di seluruh kop laporan dan kuitansi.

---

## 7. Keamanan, Hak Akses & Infrastruktur

1. **Login Ganda: Username/NISN & Google OAuth (*Login with Google*)**
   - Mendukung login akun sekolah via Google (dengan pembatasan domain email resmi sekolah opsional) maupun username/NISN + password.
2. **Manajemen Role & Matriks Hak Akses Dinamis**
   - Tiga peran pengguna (**Administrator**, **Petugas TU/Kasir**, dan **Siswa**) dengan matriks izin modul yang dapat diatur langsung dari menu *Manajemen Role*.
3. **Log Aktivitas (*Audit Trail*)**
   - Mencatat setiap aktivitas sensitif (pembuatan/pembatalan pembayaran, perubahan role, kenaikan kelas, impor data) lengkap dengan waktu, nama pelaku, deskripsi, dan alamat IP.
4. **Performa Tinggi & Siap Produksi**
   - Dibangun di atas Laravel & Filament modern dengan dukungan penuh caching in-memory **Redis**, **PHP OPcache**, serta skrip otomatis untuk *deployment zero-downtime* (`deploy.sh`), *rollback* instan, dan pencadangan terenkripsi (`backup.sh`).
