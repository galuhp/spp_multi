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
   - **Pengelompokan Bulan SPP per Tahun Kalender & Info Tahun Ajaran**: Di dalam modal pilih tagihan, daftar bulan SPP dikelompokkan rapi per tahun kalender (**Semester Ganjil Juli–Desember** dan **Semester Genap Januari–Juni**) lengkap dengan banner informasi **Tahun Ajaran** di bagian atas.
   - **Penyaringan Cerdas Bulan Lunas**: Apabila bulan SPP tertentu (misal Juni) **sudah lunas atau ditanggung pemerintah pada seluruh siswa yang dicentang**, maka *checkbox* bulan tersebut otomatis disembunyikan agar kasir fokus pada bulan yang masih memiliki tagihan terbuka.
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

5. **Transaksi Pengeluaran Operasional Sekolah (Kas Keluar)**
   - **Pencatatan Belanja Sekolah Lengkap**: Mencatat seluruh pengeluaran kas operasional (Operasional Kantor, Gaji & Honor Guru/Staf, ATK & Percetakan, Sarana & Prasarana, Kegiatan Pembelajaran/Siswa, Utilitas Listrik/Air/Internet, Pemeliharaan & Kebersihan, Konsumsi, dsb.).
   - **Penomoran Voucher Otomatis**: Setiap transaksi pengeluaran mendapatkan kode bukti unik otomatis (`EXP-YYYYMMDD-XXXX`).
   - **Unggah Bukti Transaksi Fisik**: Menyertakan foto/berkas nota, kuitansi, atau faktur pembelian.
   - **Cetak Bukti Pengeluaran Kas (Kuitansi Keluar)**: Dilengkapi format cetak dokumen resmi ber-kop sekolah dengan tanda tangan Penerima Uang, Bendahara Sekolah, dan mengetahui Kepala Sekolah.
   - **Filter & Rekapitulasi Real-Time**: Dilengkapi filter berdasarkan kategori, metode pembayaran, tahun ajaran, dan rentang tanggal dengan ringkasan otomatis total nominal pengeluaran.

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
6. **Cetak Tunggakan Siswa per Kelas (Distribusi Wali Kelas)**
   - Mendukung pencetakan daftar tunggakan yang dipisahkan rapi per rombel kelas (*page-break* otomatis untuk setiap kelas atau cetak langsung dari tombol aksi per kelas).
   - Setiap lembar cetak kelas memuat kop surat sekolah resmi, identitas rombel, tabel siswa menunggak (NISN, Nama, Tarif SPP, Tunggakan SPP, Tunggakan Non-SPP, Total Tunggakan, Bulan Menunggak), subtotal per kelas, serta lembar tanda tangan **Wali Kelas** dan **Bendahara Sekolah**.
7. **Laporan & Rekapitulasi Pengeluaran Sekolah (Cetak Rekap Kas Keluar)**
   - Format cetak dokumen resmi (A4 Landscape) yang seragam dengan rekap penerimaan pembayaran.
   - Memuat kop surat resmi, filter periode tanggal, kategori pos belanja, metode bayar, nomor bukti (`EXP-YYYYMMDD-XXXX`), uraian keperluan, penerima, total pengeluaran, kalimat terbilang Rupiah otomatis, serta tanda tangan Kepala Sekolah dan Bendahara Keuangan.
   - Tersedia di halaman khusus **Laporan Pengeluaran** serta tombol aksi cepat **Cetak Rekap Pengeluaran** pada tabel Transaksi Pengeluaran.

---

## 4. Modul Portal Mandiri Siswa & Orang Tua

1. **Dashboard & Kartu Status SPP 12 Bulan**
   - Siswa/orang tua dapat login menggunakan **NISN** untuk melihat status pelunasan setiap bulan dari Juli s.d. Juni secara visual.
2. **Rincian Tagihan & Biaya Non-SPP**
   - Menampilkan total yang sudah dibayar, sisa kewajiban SPP maupun pos non-SPP, serta penanda bulan yang ditanggung pemerintah.
   - **Deteksi Tunggakan Tahun Ajaran Lalu**: Jika siswa memiliki tunggakan dari kelas/tahun ajaran sebelumnya, sistem otomatis menampilkan kartu peringatan khusus dengan rincian bulan dan nominal yang wajib dilunasi.
3. **Riwayat Transaksi & Cetak Ulang Kuitansi Mandiri**
   - Unduh atau cetak ulang kuitansi pembayaran resmi kapan saja dari HP atau komputer rumah.

---

## 5. Modul Kesiswaan & Siklus Tahun Ajaran Lintas Jenjang (SD / SMP / SMA / SMK)

1. **Data Induk Siswa, Angkatan Masuk & Wali**
   - Pencatatan biodata lengkap (NISN, NIS, NIK, TTL, Agama, Alamat, Tanggal Masuk, **Tahun Masuk / Angkatan**) beserta data Ayah, Ibu, dan Nomor HP Wali.
   - Saat siswa didaftarkan (misal masuk tahun `2026`), sistem otomatis mencatat penempatan kelas awalnya di `riwayat_kelas` untuk tahun ajaran berjalan sehingga masa studi siswa (3 tahun untuk SMP/SMA/SMK atau 6 tahun untuk SD) terekam berkesinambungan.
2. **Impor Siswa Massal via Excel & Penetapan Tarif Baku per Tingkat**
   - Unggah ribuan data siswa lewat template Excel resmi yang memuat lembar rujukan kelas, kompetensi keahlian, dan tarif SPP per tingkat.
   - **Tarif Baku per Tingkat**: Seluruh siswa dalam satu tingkat rombel otomatis terkunci mengikuti tarif SPP baku tingkat tersebut (contoh: Tingkat 10/11/12) sehingga tidak terjadi ketidaksesuaian tarif.
   - Dilengkapi pratinjau validasi per baris (*Valid* / *Error* beserta alasan detailnya), opsi timpa data lama, dan ringkasan hasil impor sebelum benar-benar disimpan ke database.
3. **Kenaikan Kelas Massal (*Batch*) & Retensi Tunggakan Lintas Tahun Ajaran**
   - **Tunggakan Dihiraukan Saat Kenaikan**: Siswa yang memiliki tunggakan **tetap dapat dinaikkan kelas**; tunggakan tidak menghambat proses kenaikan. Seluruh saldo tunggakan otomatis terbawa ke riwayat dan tetap wajib dilunasi di loket kasir pada tahun ajaran baru.
   - **Tombol & Kotak Pratinjau Interaktif**: Dilengkapi fitur pratinjau yang menampilkan ringkasan jumlah siswa naik, tinggal, dan total tunggakan sebelum dieksekusi secara permanen.
   - **Pilihan Kelas Tujuan Batch Otomatis**: Saat memilih Kelas Asal (misal `IX RPL 1` atau `Kelas 1A`), sistem otomatis menyarankan Kelas Tujuan tingkat berikutnya (`X RPL 1` atau `Kelas 2A`) dan mengisi kolom Kelas Tujuan seluruh siswa secara serentak (*batch*).
   - **Override Manual per Siswa**: Setelah terisi secara *batch*, operator tetap bebas mengubah Kelas Tujuan pada siswa tertentu secara manual di tabel (mendukung **pola SMK** yang jurusannya tetap selama sekolah maupun **pola SD/SMP** yang bisa lintas nama rombel seperti `Kelas 1A` → `Kelas 2B`).
   - **Jejak Riwayat Kelas & Tarif SPP Historis**: Menyimpan `kelas_id` dan `spp_id` per `tahun_ajaran_id` di tabel `riwayat_kelas`, sekaligus memperbarui kelas aktif dan tarif SPP siswa ke Tahun Ajaran tujuan.
   - Dilengkapi fitur **Batalkan Batch (*Rollback*)** apabila belum ada pembayaran di tahun ajaran baru.
4. **Kelulusan Siswa Tingkat Akhir (Proteksi Wajib Bebas Tunggakan)**
   - **Proteksi Ketat Bebas Tunggakan**: Berbeda dengan kenaikan kelas, **kelulusan mewajibkan pelunasan penuh**. Siswa yang masih memiliki tunggakan SPP/Non-SPP otomatis terkunci (*checkbox disabled*) dan **tidak dapat diluluskan** sebelum seluruh tunggakan dilunasi.
   - Meluluskan siswa tingkat akhir yang bebas tunggakan secara massal, memindahkan ke status arsip, serta fitur koreksi (keluarkan dari arsip dengan alasan tercatat).

---

## 6. Modul Master Data & Pengaturan Sekolah

1. **Master Tahun Ajaran** — Pengelolaan periode tahun ajaran aktif dan historis (jadwal lintas tahun kalender Juli–Juni).
2. **Master Kompetensi Keahlian (Jurusan) & Master Rombel Kelas Lintas Tahun Ajaran** — Kelas (`X RPL 1`, `XI RPL 1`, `IX-A`, `Kelas 1A`, dst. untuk tingkat `1`–`12` / `I`–`XII`) bersifat **Master Rombel lintas tahun ajaran** sehingga tidak perlu membuat ulang kelas yang sama setiap ganti tahun ajaran.
3. **Master Tarif SPP per Tahun Ajaran & Pos Biaya Non-SPP** — Tagihan mengikuti **Tahun Ajaran**, mendukung tarif SPP per tingkat kelas maupun biaya sekali bayar/cicilan (Uang Gedung, Seragam, Praktik, Ujian, atau kategori kustom).
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

---

## 8. Segmentasi Paket Produk (Basic vs Pro)

Aplikasi telah dilengkapi sistem *feature gate* bawaan (`SPP_TIER=basic|pro`) untuk memudahkan pemasaran dalam dua edisi:

| Fitur / Modul | Paket **Basic** | Paket **Pro** |
|---|:---:|:---:|
| Master Data (Tahun Ajaran, Jurusan, Master Kelas Lintas TA, Siswa, Tarif SPP & Non-SPP) | ✓ (Kuota s.d. 200 Siswa Aktif) | ✓ (**Unlimited Siswa**) |
| Entri Pembayaran SPP Standar & Cetak Kuitansi | ✓ | ✓ |
| Laporan Penerimaan & Laporan Tunggakan Dasar | ✓ | ✓ |
| **Loket Kasir Cepat (*POS Style*)** | — | ✓ |
| **Transaksi Pengeluaran Operasional Sekolah & Cetak Voucher Kas Keluar** | — | ✓ |
| **Pembayaran Gabungan / Kolektif Kelas & Rekonsiliasi Bank (Excel)** | — | ✓ |
| **Impor Siswa Massal via Excel (*Staging Validation* & Tarif Baku Tingkat)** | — | ✓ |
| **Kenaikan Kelas *Batch*, Retensi Tunggakan & Kelulusan Massal + *Rollback*** | — | ✓ |
| **Cetak Tunggakan per Kelas (Lembar Distribusi Wali Kelas)** | — | ✓ |
| **Tanggungan Pemerintah / Beasiswa & Laporan Beasiswa** | — | ✓ |
| **Rekap Potensi vs Realisasi Penerimaan & Kartu Pembayaran Siswa (*Ledger*)** | — | ✓ |
| **Verifikasi QR Code Kuitansi Publik Anti-Palsu (HMAC-SHA256)** | — | ✓ |
| **Portal Mandiri Siswa & Orang Tua** | — | ✓ |
| **Login Google OAuth & Manajemen Matriks Hak Akses + Log Audit** | — | ✓ |

