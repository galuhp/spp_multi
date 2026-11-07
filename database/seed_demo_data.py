#!/usr/bin/env python3
import sqlite3
import os
import openpyxl
from datetime import datetime, date

DB_PATH = '/home/antigravity/galuhp/spp/database/database.sqlite'
EXCEL_PATH = '/home/antigravity/tarif_biaya_sekolah.xlsx'

def init_db():
    if os.path.exists(DB_PATH):
        os.remove(DB_PATH)

    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()

    # Enable foreign keys
    cursor.execute("PRAGMA foreign_keys = ON;")

    # 1. users
    cursor.executescript("""
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(255) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NULL UNIQUE,
        email_verified_at DATETIME NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'petugas',
        remember_token VARCHAR(100) NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 2. password_reset_tokens
    cursor.executescript("""
    CREATE TABLE password_reset_tokens (
        email VARCHAR(255) PRIMARY KEY,
        token VARCHAR(255) NOT NULL,
        created_at DATETIME NULL
    );
    """)

    # 3. sessions
    cursor.executescript("""
    CREATE TABLE sessions (
        id VARCHAR(255) PRIMARY KEY,
        user_id INTEGER NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        payload TEXT NOT NULL,
        last_activity INTEGER NOT NULL
    );
    CREATE INDEX sessions_user_id_index ON sessions (user_id);
    CREATE INDEX sessions_last_activity_index ON sessions (last_activity);
    """)

    # 4. cache
    cursor.executescript("""
    CREATE TABLE cache (
        key VARCHAR(255) PRIMARY KEY,
        value TEXT NOT NULL,
        expiration INTEGER NOT NULL
    );
    CREATE TABLE cache_locks (
        key VARCHAR(255) PRIMARY KEY,
        owner VARCHAR(255) NOT NULL,
        expiration INTEGER NOT NULL
    );
    """)

    # 5. jobs
    cursor.executescript("""
    CREATE TABLE jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        queue VARCHAR(255) NOT NULL,
        payload TEXT NOT NULL,
        attempts INTEGER NOT NULL,
        reserved_at INTEGER NULL,
        available_at INTEGER NOT NULL,
        created_at INTEGER NOT NULL
    );
    CREATE INDEX jobs_queue_index ON jobs (queue);
    CREATE TABLE job_batches (
        id VARCHAR(255) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        total_jobs INTEGER NOT NULL,
        pending_jobs INTEGER NOT NULL,
        failed_jobs INTEGER NOT NULL,
        failed_job_ids TEXT NOT NULL,
        options TEXT NULL,
        cancelled_at INTEGER NULL,
        created_at INTEGER NOT NULL,
        finished_at INTEGER NULL
    );
    CREATE TABLE failed_jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid VARCHAR(255) NOT NULL UNIQUE,
        connection TEXT NOT NULL,
        queue TEXT NOT NULL,
        payload TEXT NOT NULL,
        exception TEXT NOT NULL,
        failed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    """)

    # 6. meui_preferences
    cursor.executescript("""
    CREATE TABLE meui_preferences (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        key VARCHAR(255) NOT NULL,
        value TEXT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 7. jurusans
    cursor.executescript("""
    CREATE TABLE jurusans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kode VARCHAR(10) NOT NULL UNIQUE,
        nama VARCHAR(100) NOT NULL,
        deskripsi TEXT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 8. tahun_ajarans
    cursor.executescript("""
    CREATE TABLE tahun_ajarans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama VARCHAR(20) NOT NULL UNIQUE,
        tanggal_mulai DATE NOT NULL,
        tanggal_selesai DATE NOT NULL,
        aktif BOOLEAN DEFAULT 0,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 9. kelas
    cursor.executescript("""
    CREATE TABLE kelas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        jurusan_id INTEGER NOT NULL REFERENCES jurusans(id) ON DELETE CASCADE,
        tahun_ajaran_id INTEGER NULL REFERENCES tahun_ajarans(id) ON DELETE SET NULL,
        wali_kelas_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
        nama_kelas VARCHAR(50) NOT NULL,
        tingkat VARCHAR(10) NOT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    CREATE INDEX kelas_tahun_ajaran_tingkat_idx ON kelas (tahun_ajaran_id, tingkat);
    """)

    # 10. spps
    cursor.executescript("""
    CREATE TABLE spps (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kategori VARCHAR(50) DEFAULT 'SPP',
        tipe VARCHAR(30) DEFAULT 'bulanan',
        nama VARCHAR(100) NULL,
        tahun INTEGER NOT NULL,
        tahun_ajaran VARCHAR(20) NULL,
        tingkat VARCHAR(10) NULL,
        nominal INTEGER NOT NULL,
        keterangan TEXT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    CREATE INDEX spps_kategori_index ON spps (kategori);
    CREATE INDEX spps_tingkat_index ON spps (tingkat);
    """)

    # 11. siswas
    cursor.executescript("""
    CREATE TABLE siswas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
        kelas_id INTEGER NOT NULL REFERENCES kelas(id) ON DELETE RESTRICT,
        spp_id INTEGER NOT NULL REFERENCES spps(id) ON DELETE RESTRICT,
        nisn VARCHAR(10) NOT NULL UNIQUE,
        nis VARCHAR(8) NOT NULL UNIQUE,
        nama VARCHAR(100) NOT NULL,
        jenis_kelamin VARCHAR(1) NOT NULL,
        alamat TEXT NOT NULL,
        no_telp VARCHAR(20) NOT NULL,
        nik VARCHAR(16) NULL,
        tempat_lahir VARCHAR(50) NULL,
        tanggal_lahir DATE NULL,
        agama VARCHAR(20) NULL,
        tanggal_masuk DATE NULL,
        tahun_masuk INTEGER NULL,
        is_archived BOOLEAN DEFAULT 0,
        archived_at DATETIME NULL,
        archive_reason TEXT NULL,
        status VARCHAR(20) DEFAULT 'aktif',
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    CREATE INDEX siswas_kelas_archived_status_idx ON siswas (kelas_id, is_archived, status);
    CREATE INDEX siswas_nisn_index ON siswas (nisn);
    """)

    # 12. wali
    cursor.executescript("""
    CREATE TABLE wali (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        siswa_id INTEGER NOT NULL UNIQUE REFERENCES siswas(id) ON DELETE CASCADE,
        nama_ayah VARCHAR(100) NULL,
        nama_ibu VARCHAR(100) NULL,
        nama_wali VARCHAR(100) NULL,
        hp_wali VARCHAR(20) NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 13. proses_kelas_batch
    cursor.executescript("""
    CREATE TABLE proses_kelas_batch (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kode_batch VARCHAR(50) NOT NULL UNIQUE,
        jenis VARCHAR(20) NOT NULL,
        tahun_ajaran_asal_id INTEGER NULL REFERENCES tahun_ajarans(id),
        tahun_ajaran_tujuan_id INTEGER NULL REFERENCES tahun_ajarans(id),
        total_siswa INTEGER DEFAULT 0,
        catatan TEXT NULL,
        dieksekusi_oleh INTEGER NULL REFERENCES users(id),
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 14. riwayat_kelas
    cursor.executescript("""
    CREATE TABLE riwayat_kelas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        siswa_id INTEGER NOT NULL REFERENCES siswas(id) ON DELETE CASCADE,
        kelas_id INTEGER NOT NULL REFERENCES kelas(id) ON DELETE RESTRICT,
        tahun_ajaran_id INTEGER NOT NULL REFERENCES tahun_ajarans(id) ON DELETE RESTRICT,
        spp_id INTEGER NULL REFERENCES spps(id) ON DELETE SET NULL,
        proses_kelas_batch_id INTEGER NULL REFERENCES proses_kelas_batch(id) ON DELETE SET NULL,
        status VARCHAR(20) DEFAULT 'aktif',
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    CREATE INDEX riwayat_kelas_ta_kelas_idx ON riwayat_kelas (tahun_ajaran_id, kelas_id);
    """)

    # 15. pembayarans
    cursor.executescript("""
    CREATE TABLE pembayarans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kode_transaksi VARCHAR(50) NOT NULL,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
        siswa_id INTEGER NOT NULL REFERENCES siswas(id) ON DELETE RESTRICT,
        spp_id INTEGER NOT NULL REFERENCES spps(id) ON DELETE RESTRICT,
        tgl_bayar DATE NOT NULL,
        bulan_dibayar VARCHAR(20) NULL,
        tahun_dibayar INTEGER NULL,
        jumlah_bayar INTEGER NOT NULL,
        metode_pembayaran VARCHAR(50) DEFAULT 'Tunai',
        catatan TEXT NULL,
        void_at DATETIME NULL,
        void_by INTEGER NULL REFERENCES users(id),
        void_reason TEXT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    CREATE INDEX pembayarans_siswa_id_index ON pembayarans (siswa_id);
    CREATE INDEX pembayarans_tgl_bayar_index ON pembayarans (tgl_bayar);
    """)

    # 16. log_aktivitas
    cursor.executescript("""
    CREATE TABLE log_aktivitas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
        action VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        subject_type VARCHAR(255) NULL,
        subject_id INTEGER NULL,
        properties TEXT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 17. profil_sekolahs
    cursor.executescript("""
    CREATE TABLE profil_sekolahs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        app_name VARCHAR(100) NULL,
        logo VARCHAR(255) NULL,
        nama_sekolah VARCHAR(150) NOT NULL,
        npsn VARCHAR(20) NULL,
        jenjang VARCHAR(20) DEFAULT 'SMK',
        pemerintah_daerah VARCHAR(150) NULL,
        dinas_pendidikan VARCHAR(150) NULL,
        alamat TEXT NULL,
        kota VARCHAR(50) NULL,
        kode_pos VARCHAR(10) NULL,
        no_telp VARCHAR(30) NULL,
        email VARCHAR(100) NULL,
        website VARCHAR(100) NULL,
        nama_kepala_sekolah VARCHAR(100) NULL,
        nip_kepala_sekolah VARCHAR(30) NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 18. role_permissions
    cursor.executescript("""
    CREATE TABLE role_permissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        role VARCHAR(20) NOT NULL,
        permission VARCHAR(50) NOT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        UNIQUE(role, permission)
    );
    """)

    # 19. pembayaran_gabungans
    cursor.executescript("""
    CREATE TABLE pembayaran_gabungans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nomor_referensi VARCHAR(50) NOT NULL UNIQUE,
        sumber VARCHAR(30) NOT NULL,
        status VARCHAR(20) DEFAULT 'posted',
        tahun_ajaran_id INTEGER NULL REFERENCES tahun_ajarans(id),
        kelas_id INTEGER NULL REFERENCES kelas(id),
        user_id INTEGER NOT NULL REFERENCES users(id),
        total_diterima INTEGER NOT NULL,
        total_dialokasikan INTEGER NOT NULL,
        selisih INTEGER DEFAULT 0,
        tgl_bayar DATE NOT NULL,
        metode_pembayaran VARCHAR(50) DEFAULT 'Setoran Kolektif',
        rekening_bank VARCHAR(100) NULL,
        nama_penyetor VARCHAR(100) NULL,
        catatan TEXT NULL,
        nama_file_impor VARCHAR(255) NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 20. pembayaran_gabungan_items
    cursor.executescript("""
    CREATE TABLE pembayaran_gabungan_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pembayaran_gabungan_id INTEGER NOT NULL REFERENCES pembayaran_gabungans(id) ON DELETE CASCADE,
        pembayaran_id INTEGER NULL REFERENCES pembayarans(id) ON DELETE SET NULL,
        siswa_id INTEGER NOT NULL REFERENCES siswas(id) ON DELETE RESTRICT,
        spp_id INTEGER NOT NULL REFERENCES spps(id) ON DELETE RESTRICT,
        bulan_dibayar VARCHAR(20) NULL,
        tahun_dibayar INTEGER NULL,
        jumlah_bayar INTEGER NOT NULL,
        status VARCHAR(20) DEFAULT 'valid',
        pesan_error TEXT NULL,
        metadata TEXT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 21. siswa_tanggungan_pemerintah
    cursor.executescript("""
    CREATE TABLE siswa_tanggungan_pemerintah (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        siswa_id INTEGER NOT NULL REFERENCES siswas(id) ON DELETE CASCADE,
        cakupan VARCHAR(20) DEFAULT 'spp',
        spp_id INTEGER NULL REFERENCES spps(id) ON DELETE CASCADE,
        mulai DATE NOT NULL,
        dicabut_at DATETIME NULL,
        alasan TEXT NULL,
        created_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 22. pengeluaran_sekolahs
    cursor.executescript("""
    CREATE TABLE pengeluaran_sekolahs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nomor_transaksi VARCHAR(40) NOT NULL UNIQUE,
        tanggal DATE NOT NULL,
        kategori VARCHAR(50) NOT NULL,
        keperluan VARCHAR(255) NOT NULL,
        nominal INTEGER NOT NULL,
        penerima VARCHAR(100) NULL,
        metode_pembayaran VARCHAR(30) DEFAULT 'Tunai',
        catatan TEXT NULL,
        bukti_transaksi VARCHAR(255) NULL,
        user_id INTEGER NOT NULL REFERENCES users(id),
        tahun_ajaran_id INTEGER NULL REFERENCES tahun_ajarans(id),
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    CREATE INDEX pengeluaran_tanggal_kategori ON pengeluaran_sekolahs (tanggal, kategori);
    CREATE INDEX pengeluaran_tahun_ajaran ON pengeluaran_sekolahs (tahun_ajaran_id);
    """)

    # 23. socialite_users
    cursor.executescript("""
    CREATE TABLE socialite_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        provider VARCHAR(50) NOT NULL,
        provider_id VARCHAR(255) NOT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 24. import_siswa_staging
    cursor.executescript("""
    CREATE TABLE import_siswa_staging (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_id VARCHAR(50) NOT NULL,
        nisn VARCHAR(20) NULL,
        nis VARCHAR(20) NULL,
        nama_lengkap VARCHAR(100) NULL,
        kelas VARCHAR(100) NULL,
        jurusan VARCHAR(50) NULL,
        jenis_kelamin VARCHAR(10) NULL,
        no_hp VARCHAR(30) NULL,
        alamat TEXT NULL,
        status_validasi VARCHAR(20) DEFAULT 'pending',
        error_message TEXT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    """)

    # 25. migrations tracking table
    cursor.executescript("""
    CREATE TABLE migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        migration VARCHAR(255) NOT NULL,
        batch INTEGER NOT NULL
    );
    """)

    # Record all applied migrations so Laravel recognizes them
    migration_files = sorted(os.listdir('/home/antigravity/galuhp/spp/database/migrations'))
    for i, f in enumerate(migration_files, 1):
        if f.endswith('.php'):
            name = f[:-4]
            cursor.execute("INSERT INTO migrations (migration, batch) VALUES (?, ?)", (name, 1))

    conn.commit()
    return conn

def seed():
    conn = init_db()
    cursor = conn.cursor()
    now_str = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    # Laravel standard bcrypt hash for 'password'
    # password = 'password'
    HASHED_PW = '$2y$12$6qV998jZJ8N0z3aG.ZJ6q.2f6385K0YjYJm5418b3H1fO12Q5hGTu'

    # 1. Users
    cursor.execute("""
    INSERT INTO users (name, username, email, password, role, created_at, updated_at) VALUES
    ('Administrator SPP', 'admin', 'admin@spp.test', ?, 'admin', ?, ?),
    ('Budi Santoso (Petugas SPP)', 'petugas', 'petugas@spp.test', ?, 'petugas', ?, ?),
    ('Wali Kelas (View Only)', 'walikelas', 'walikelas@spp.test', ?, 'wali_kelas', ?, ?);
    """, (HASHED_PW, now_str, now_str, HASHED_PW, now_str, now_str, HASHED_PW, now_str, now_str))

    admin_id = 1
    petugas_id = 2
    walikelas_id = 3

    # 2. Role Permissions
    permissions = [
        # admin
        ('admin', 'dashboard'), ('admin', 'transaksi_spp'), ('admin', 'laporan'),
        ('admin', 'laporan_tunggakan'), ('admin', 'portal_siswa'), ('admin', 'kesiswaan'),
        ('admin', 'master_data'), ('admin', 'pengaturan_akses'),
        # petugas
        ('petugas', 'dashboard'), ('petugas', 'transaksi_spp'), ('petugas', 'laporan'),
        ('petugas', 'laporan_tunggakan'), ('petugas', 'portal_siswa'), ('petugas', 'kesiswaan'),
        # wali_kelas
        ('wali_kelas', 'dashboard'), ('wali_kelas', 'laporan_tunggakan'),
        # siswa
        ('siswa', 'dashboard'), ('siswa', 'portal_siswa')
    ]
    cursor.executemany("INSERT OR IGNORE INTO role_permissions (role, permission, created_at, updated_at) VALUES (?, ?, ?, ?)",
                       [(r, p, now_str, now_str) for r, p in permissions])

    # 3. Profil Sekolah
    cursor.execute("""
    INSERT INTO profil_sekolahs (app_name, nama_sekolah, npsn, jenjang, pemerintah_daerah, dinas_pendidikan, alamat, kota, kode_pos, no_telp, email, website, nama_kepala_sekolah, nip_kepala_sekolah, created_at, updated_at)
    VALUES ('E-SPP SEKOLAH', 'SMK Negeri 1 Pembangunan', '20109988', 'SMK', 'PEMERINTAH DAERAH PROVINSI JAWA BARAT', 'DINAS PENDIDIKAN DAN KEBUDAYAAN', 'Jl. Merdeka Mandiri No. 45', 'Bandung', '40123', '(022) 7654321', 'info@smkn1pembangunan.sch.id', 'https://smkn1pembangunan.sch.id', 'Drs. H. Mulyadi Kartasasmita, M.Pd.', '19720415 199702 1 003', ?, ?);
    """, (now_str, now_str))

    # 4. Tahun Ajaran
    tas = [
        ('2024/2025', '2024-07-01', '2025-06-30', 0),
        ('2025/2026', '2025-07-01', '2026-06-30', 0),
        ('2026/2027', '2026-07-01', '2027-06-30', 1),
    ]
    cursor.executemany("INSERT INTO tahun_ajarans (nama, tanggal_mulai, tanggal_selesai, aktif, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
                       [(nama, tm, ts, aktif, now_str, now_str) for nama, tm, ts, aktif in tas])
    ta_aktif_id = 3 # 2026/2027

    # 5. Jurusans
    jurusans = [
        ('RPL', 'Rekayasa Perangkat Lunak', 'Pengembangan perangkat lunak, web, dan sistem cerdas.'),
        ('TKJ', 'Teknik Komputer dan Jaringan', 'Infrastruktur jaringan komputer, sistem server, dan cloud.'),
        ('DKV', 'Desain Komunikasi Visual', 'Desain grafis multimedia, ilustrasi, branding, dan periklanan.'),
        ('AKL', 'Akuntansi dan Keuangan Lembaga', 'Pengelolaan pembukuan keuangan lembaga dan perbankan.')
    ]
    cursor.executemany("INSERT INTO jurusans (kode, nama, deskripsi, created_at, updated_at) VALUES (?, ?, ?, ?, ?)",
                       [(k, n, d, now_str, now_str) for k, n, d in jurusans])
    rpl_id = 1
    tkj_id = 2

    # 6. Kelas (2 Kelas Utama X RPL 1 & X TKJ 1, serta 2 Kelas Tujuan Promosi XI RPL 1 & XI TKJ 1)
    kelasList = [
        (rpl_id, ta_aktif_id, walikelas_id, 'X RPL 1', '10'),
        (tkj_id, ta_aktif_id, None, 'X TKJ 1', '10'),
        (rpl_id, ta_aktif_id, None, 'XI RPL 1', '11'),
        (tkj_id, ta_aktif_id, None, 'XI TKJ 1', '11'),
    ]
    cursor.executemany("INSERT INTO kelas (jurusan_id, tahun_ajaran_id, wali_kelas_id, nama_kelas, tingkat, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
                       [(j, ta, wk, n, t, now_str, now_str) for j, ta, wk, n, t in kelasList])

    kelas_x_rpl_id = 1
    kelas_x_tkj_id = 2
    kelas_xi_rpl_id = 3
    kelas_xi_tkj_id = 4

    # 7. Spps (Tarifs) parsed from Excel
    wb = openpyxl.load_workbook(EXCEL_PATH, data_only=True)

    def tentukan_kategori(jenis: str) -> tuple[str, str]:
        j = jenis.lower()
        if 'biaya sekolah per bulan' in j or 'spp' in j:
            return ('SPP', 'bulanan')
        if 'tabungan' in j:
            return ('Tabungan', 'sekali_bayar')
        if 'pts' in j or 'pas' in j or 'anbk' in j or 'ujian' in j:
            return ('Biaya Ujian', 'sekali_bayar')
        if 'praktek' in j or 'prakerin' in j:
            return ('Biaya Praktik', 'sekali_bayar')
        if 'kegiatan' in j or 'study banding' in j:
            return ('Kegiatan Siswa', 'sekali_bayar')
        if 'kaos' in j or 'katelpak' in j or 'almamater' in j or 'seragam' in j:
            return ('Uang Seragam', 'sekali_bayar')
        if 'cicilan' in j or 'gedung' in j:
            return ('Uang Gedung', 'sekali_bayar')
        if 'foto' in j or 'tasyakuran' in j or 'kelulusan' in j:
            return ('Kelulusan', 'sekali_bayar')
        if 'map rapot' in j or 'administrasi' in j:
            return ('Administrasi', 'sekali_bayar')
        if 'bimker' in j:
            return ('Bimbingan Karier', 'sekali_bayar')
        if 'kalender' in j:
            return ('Lain-lain', 'sekali_bayar')
        return ('Lain-lain', 'sekali_bayar')

    sheet_to_tingkat = {
        'Kelas X': '10',
        'Kelas XI': '11',
        'Kelas XII': '12'
    }

    spp_tarifs = {} # tingkat -> id
    all_tarifs = [] # list of (id, tingkat, kategori, tipe, nama, nominal)
    global_spp_id = None

    for sheet_name, tingkat in sheet_to_tingkat.items():
        ws = wb[sheet_name]
        anbk_counter = 1
        for row in range(5, ws.max_row + 1):
            no = ws.cell(row, 1).value
            jenis = ws.cell(row, 2).value
            nominal = ws.cell(row, 3).value
            if not jenis or nominal is None:
                continue

            jenis_str = str(jenis).strip()
            nominal_int = int(nominal)

            nama_label = jenis_str
            if jenis_str == 'ANBK':
                nama_label = f"ANBK Gelombang {anbk_counter}"
                anbk_counter += 1

            kategori, tipe = tentukan_kategori(jenis_str)

            if kategori == 'SPP':
                if global_spp_id is None:
                    cursor.execute("""
                    INSERT INTO spps (kategori, tipe, nama, tahun, tahun_ajaran, tingkat, nominal, keterangan, created_at, updated_at)
                    VALUES (?, ?, ?, 2026, '2026/2027', NULL, ?, 'Tarif Biaya Sekolah per bulan (Berlaku untuk semua tingkat) TA 2026/2027', ?, ?)
                    """, (kategori, tipe, nama_label, nominal_int, now_str, now_str))
                    global_spp_id = cursor.lastrowid
                    all_tarifs.append({
                        'id': global_spp_id,
                        'tingkat': None,
                        'kategori': kategori,
                        'tipe': tipe,
                        'nama': nama_label,
                        'nominal': nominal_int
                    })
                current_id = global_spp_id
                spp_tarifs[tingkat] = global_spp_id
                continue

            cursor.execute("""
            INSERT INTO spps (kategori, tipe, nama, tahun, tahun_ajaran, tingkat, nominal, keterangan, created_at, updated_at)
            VALUES (?, ?, ?, 2026, '2026/2027', ?, ?, ?, ?, ?)
            """, (kategori, tipe, nama_label, tingkat, nominal_int, f"Tarif {nama_label} Tingkat {tingkat} TA 2026/2027", now_str, now_str))

            current_id = cursor.lastrowid
            all_tarifs.append({
                'id': current_id,
                'tingkat': tingkat,
                'kategori': kategori,
                'tipe': tipe,
                'nama': nama_label,
                'nominal': nominal_int
            })

    # 8. Seed Siswas & Walis (2 Kelas, 10 Siswa per Kelas, Seluruh Nama Unik 100%)
    siswa_data_x_rpl = [
        ('Aditya Pratama', 'L', 10001001, 26001, True), # Akun portal siswa: username 'siswa', password 'password'
        ('Annisa Larasati', 'P', 10001002, 26002, False),
        ('Bagas Wicaksono', 'L', 10001003, 26003, False),
        ('Bella Permata', 'P', 10001004, 26004, False),
        ('Candra Gunawan', 'L', 10001005, 26005, False),
        ('Cantika Maharani', 'P', 10001006, 26006, False), # Beasiswa
        ('Dimas Setiawan', 'L', 10001007, 26007, False),
        ('Dian Anggraini', 'P', 10001008, 26008, False),
        ('Eko Prasetyo', 'L', 10001009, 26009, False),
        ('Eka Rahmawati', 'P', 10001010, 26010, False),
    ]

    siswa_data_x_tkj = [
        ('Farhan Hidayat', 'L', 10002001, 26021, False),
        ('Fitri Handayani', 'P', 10002002, 26022, False),
        ('Gilang Ramadhan', 'L', 10002003, 26023, False),
        ('Gita Nirmala', 'P', 10002004, 26024, False),
        ('Hafiz Nugroho', 'L', 10002005, 26025, False),
        ('Hesti Wulandari', 'P', 10002006, 26026, False),
        ('Irfan Kurniawan', 'L', 10002007, 26027, False),
        ('Indah Puspitasari', 'P', 10002008, 26028, False),
        ('Jaka Santoso', 'L', 10002009, 26029, False),
        ('Jasmine Zahra', 'P', 10002010, 26030, False),
    ]

    def insert_students(student_list, kelas_id, spp_id, tingkat):
        created_ids = []
        for nama, jk, nisn, nis, is_portal in student_list:
            user_id = None
            if is_portal:
                cursor.execute("""
                INSERT INTO users (name, username, email, password, role, created_at, updated_at)
                VALUES (?, 'siswa', 'siswa@spp.test', ?, 'siswa', ?, ?)
                """, (nama, HASHED_PW, now_str, now_str))
                user_id = cursor.lastrowid

            cursor.execute("""
            INSERT INTO siswas (user_id, kelas_id, spp_id, nisn, nis, nama, jenis_kelamin, alamat, no_telp, tahun_masuk, status, is_archived, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 2026, 'aktif', 0, ?, ?)
            """, (user_id, kelas_id, spp_id, str(nisn), str(nis), nama, jk, f"Jl. Pemuda Mandiri No. {str(nis)[-2:]}, Bandung", f"0812{tingkat}{str(nisn)[-4:]}", now_str, now_str))
            s_id = cursor.lastrowid
            created_ids.append(s_id)

            nama_depan = nama.split()[0]
            cursor.execute("""
            INSERT INTO wali (siswa_id, nama_ayah, nama_ibu, hp_wali, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?)
            """, (s_id, f"Bapak {nama_depan}", f"Ibu {nama_depan}", f"0813{tingkat}{str(nisn)[-4:]}", now_str, now_str))

            cursor.execute("""
            INSERT INTO riwayat_kelas (siswa_id, kelas_id, tahun_ajaran_id, spp_id, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'aktif', ?, ?)
            """, (s_id, kelas_id, ta_aktif_id, spp_id, now_str, now_str))
        return created_ids

    siswas_x_rpl = insert_students(siswa_data_x_rpl, kelas_x_rpl_id, spp_tarifs['10'], '10')
    siswas_x_tkj = insert_students(siswa_data_x_tkj, kelas_x_tkj_id, spp_tarifs['10'], '10')

    # 9. Tanggungan Pemerintah / Beasiswa (Demo)
    cursor.execute("""
    INSERT INTO siswa_tanggungan_pemerintah (siswa_id, spp_id, cakupan, mulai, alasan, created_by, created_at, updated_at)
    VALUES (?, NULL, 'spp', '2026-07-01', 'Beasiswa Prestasi Kurang Mampu (Disdik Jabar)', ?, ?, ?);
    """, (siswas_x_rpl[5], petugas_id, now_str, now_str))

    # 10. Seed Realistic Payments for Demo
    months_ta = ['Juli', 'Agustus', 'September', 'Oktober', 'November']
    kode_counter = 1
    non_spp_10 = [t for t in all_tarifs if t['tingkat'] == '10' and t['kategori'] != 'SPP']
    semua_siswa = siswas_x_rpl + siswas_x_tkj

    for idx, s_id in enumerate(semua_siswa):
        mod = idx % 10
        if mod in (0, 1):
            # Lunas 100% SPP + Non SPP
            for b in months_ta:
                kode = f"SPP-2026{kode_counter:05d}"
                kode_counter += 1
                cursor.execute("""
                INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
                VALUES (?, ?, ?, ?, '2026-10-15', ?, 2026, 100000, 'Tunai', ?, ?, ?)
                """, (kode, petugas_id, s_id, spp_tarifs['10'], b, f"Pembayaran SPP Bulan {b}", now_str, now_str))
            for t in non_spp_10:
                kode = f"NSP-2026{kode_counter:05d}"
                kode_counter += 1
                cursor.execute("""
                INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
                VALUES (?, ?, ?, ?, '2026-09-10', NULL, 2026, ?, 'Tunai', ?, ?, ?)
                """, (kode, petugas_id, s_id, t['id'], t['nominal'], f"Pelunasan {t['nama']}", now_str, now_str))
        elif mod in (2, 3):
            # Bayar 3 bulan SPP + beberapa non SPP
            for b in months_ta[:3]:
                kode = f"SPP-2026{kode_counter:05d}"
                kode_counter += 1
                cursor.execute("""
                INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
                VALUES (?, ?, ?, ?, '2026-09-15', ?, 2026, 100000, 'Tunai', ?, ?, ?)
                """, (kode, petugas_id, s_id, spp_tarifs['10'], b, f"Pembayaran SPP Bulan {b}", now_str, now_str))
            for t in non_spp_10[:3]:
                kode = f"NSP-2026{kode_counter:05d}"
                kode_counter += 1
                cursor.execute("""
                INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
                VALUES (?, ?, ?, ?, '2026-08-20', NULL, 2026, ?, 'Tunai', ?, ?, ?)
                """, (kode, petugas_id, s_id, t['id'], t['nominal'], f"Pembayaran {t['nama']}", now_str, now_str))
        elif mod == 4:
            # Bayar 1 bulan SPP
            kode = f"SPP-2026{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2026-07-20', 'Juli', 2026, 100000, 'Tunai', 'Pembayaran SPP Bulan Juli', ?, ?)
            """, (kode, petugas_id, s_id, spp_tarifs['10'], now_str, now_str))
        elif mod in (6, 7):
            # Bayar 2 bulan SPP via Transfer
            for b in months_ta[:2]:
                kode = f"SPP-2026{kode_counter:05d}"
                kode_counter += 1
                cursor.execute("""
                INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
                VALUES (?, ?, ?, ?, '2026-08-15', ?, 2026, 100000, 'Transfer Bank', ?, ?, ?)
                """, (kode, petugas_id, s_id, spp_tarifs['10'], b, f"Pembayaran SPP Bulan {b}", now_str, now_str))

    # 11. Seed School Operational Expenditures (Pengeluaran Operasional Sekolah)
    pengeluarans = [
        ('EXP-20261001-0001', '2026-10-01', 'Gaji & Honor Guru/Staf', 'Honor Guru Honorer dan Staf Kebersihan Bulan September', 4500000, 'Bendahara / Guru Honorer', 'Transfer Bank'),
        ('EXP-20261008-0002', '2026-10-08', 'ATK & Percetakan', 'Pengadaan Kertas HVS, Tinta Printer & Penggandaan Naskah PTS', 1250000, 'CV Media Prima Grafika', 'Tunai'),
        ('EXP-20261015-0003', '2026-10-15', 'Listrik, Air & Internet', 'Pembayaran Tagihan Listrik PLN & Internet Dedicated Fiber', 1850000, 'PT Telkom Indonesia & PLN', 'Transfer Bank'),
        ('EXP-20261022-0004', '2026-10-22', 'Sarana & Prasarana', 'Penggantian Switch Hub Gigabit & Kabel LAN Lab Komputer RPL', 950000, 'Toko Netkom Mandiri', 'Tunai'),
        ('EXP-20261028-0005', '2026-10-28', 'Pemeliharaan & Kebersihan', 'Service dan Cuci AC Ruang Guru dan Ruang Kasir TU', 450000, 'CV Sejuk Mandiri Teknik', 'Tunai'),
        ('EXP-20261102-0006', '2026-11-02', 'Konsumsi Rapat/Tamu', 'Konsumsi Rapat Koordinasi Wali Kelas & Sosialisasi Ujian', 350000, 'Catering Barokah', 'Tunai'),
    ]
    cursor.executemany("""
    INSERT INTO pengeluaran_sekolahs (nomor_transaksi, tanggal, kategori, keperluan, nominal, penerima, metode_pembayaran, user_id, tahun_ajaran_id, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    """, [(nt, tgl, kat, kep, nom, pen, met, petugas_id, ta_aktif_id, now_str, now_str) for nt, tgl, kat, kep, nom, pen, met in pengeluarans])

    conn.commit()
    conn.close()

    print(f"Data seeding completed successfully into {DB_PATH}")
    print(f"Total students: {len(siswas_x_rpl) + len(siswas_x_tkj)}")
    print(f"Total tariffs: {len(all_tarifs)}")

if __name__ == '__main__':
    seed()
