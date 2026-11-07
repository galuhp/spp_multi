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
        spp_id INTEGER NULL REFERENCES spps(id) ON DELETE CASCADE,
        cakupan VARCHAR(20) NOT NULL,
        tanggal_mulai DATE NOT NULL,
        tanggal_berakhir DATE NULL,
        keterangan TEXT NULL,
        dicabut_pada DATETIME NULL,
        alasan_pencabutan TEXT NULL,
        dicabut_oleh INTEGER NULL REFERENCES users(id),
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

    # 6. Kelas
    # X: 53 siswa total (X RPL 1: 27 siswa, X TKJ 1: 26 siswa) -> wali kelas Siti Aminah on X RPL 1
    # XI: 56 siswa total (XI RPL 1: 28 siswa, XI TKJ 1: 28 siswa)
    # XII: 52 siswa total (XII RPL 1: 26 siswa, XII TKJ 1: 26 siswa)
    kelasList = [
        (rpl_id, ta_aktif_id, walikelas_id, 'X RPL 1', '10'),
        (tkj_id, ta_aktif_id, None, 'X TKJ 1', '10'),
        (rpl_id, ta_aktif_id, None, 'XI RPL 1', '11'),
        (tkj_id, ta_aktif_id, None, 'XI TKJ 1', '11'),
        (rpl_id, ta_aktif_id, None, 'XII RPL 1', '12'),
        (tkj_id, ta_aktif_id, None, 'XII TKJ 1', '12'),
    ]
    cursor.executemany("INSERT INTO kelas (jurusan_id, tahun_ajaran_id, wali_kelas_id, nama_kelas, tingkat, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
                       [(j, ta, wk, n, t, now_str, now_str) for j, ta, wk, n, t in kelasList])

    kelas_x_rpl_id = 1
    kelas_x_tkj_id = 2
    kelas_xi_rpl_id = 3
    kelas_xi_tkj_id = 4
    kelas_xii_rpl_id = 5
    kelas_xii_tkj_id = 6

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

    # 8. Seed Siswas & Walis
    # Generate realistic names
    first_names_l = ["Aditya", "Bagas", "Dimas", "Fajar", "Gilang", "Hafiz", "Irfan", "Jaka", "Kevin", "Lukman", "Maulana", "Naufal", "Pratama", "Raditya", "Rizky", "Satria", "Taufik", "Wahyu", "Yusuf", "Zaki", "Arif", "Bayu", "Candra", "Danang", "Eko", "Farhan", "Galih", "Hendro", "Iqbal", "Kurniawan"]
    first_names_p = ["Annisa", "Bella", "Cantika", "Dian", "Eka", "Fitri", "Gita", "Hesti", "Indah", "Jasmine", "Kartika", "Lestari", "Maya", "Nadia", "Putri", "Rani", "Siti", "Tiara", "Ulfa", "Wulan", "Yulia", "Zahra", "Aulia", "Citra", "Dewi", "Erna", "Febri", "Halimah", "Intan", "Kusuma"]
    last_names = ["Saputra", "Pratama", "Wibowo", "Kusuma", "Hidayat", "Nugraha", "Santoso", "Wijaya", "Permana", "Setiawan", "Utomo", "Kurniawan", "Ramadhan", "Siregar", "Nasution", "Firmansyah", "Gunawan", "Mahendra", "Purnama", "Subagja"]

    def generate_students(count, tingkat, kelas_id, spp_id, start_nisn, start_nis, tahun_masuk):
        students = []
        for i in range(count):
            is_male = (i % 2 == 0)
            jk = 'L' if is_male else 'P'
            fn = first_names_l[i % len(first_names_l)] if is_male else first_names_p[i % len(first_names_p)]
            ln = last_names[(i * 3 + int(tingkat)) % len(last_names)]
            nama = f"{fn} {ln}"
            nisn = str(start_nisn + i)
            nis = str(start_nis + i)
            no_telp = f"0812{tingkat}{i:04d}"
            alamat = f"Jl. Pemuda No. {i+1}, RT 0{((i%5)+1):02d}/RW 0{((i%3)+1):02d}, Bandung"

            user_id = None
            if tingkat == '10' and kelas_id == kelas_x_rpl_id and i == 0:
                # Demo student user account
                cursor.execute("""
                INSERT INTO users (name, username, email, password, role, created_at, updated_at)
                VALUES (?, 'siswa', 'siswa@spp.test', ?, 'siswa', ?, ?)
                """, (nama, HASHED_PW, now_str, now_str))
                user_id = cursor.lastrowid

            cursor.execute("""
            INSERT INTO siswas (user_id, kelas_id, spp_id, nisn, nis, nama, jenis_kelamin, alamat, no_telp, tahun_masuk, status, is_archived, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif', 0, ?, ?)
            """, (user_id, kelas_id, spp_id, nisn, nis, nama, jk, alamat, no_telp, tahun_masuk, now_str, now_str))

            siswa_id = cursor.lastrowid
            students.append(siswa_id)

            # Insert Wali
            nama_ayah = f"Ayah {nama.split()[0]}"
            nama_ibu = f"Ibu {nama.split()[0]}"
            hp_wali = f"0813{tingkat}{i:04d}"
            cursor.execute("""
            INSERT INTO wali (siswa_id, nama_ayah, nama_ibu, hp_wali, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?)
            """, (siswa_id, nama_ayah, nama_ibu, hp_wali, now_str, now_str))

            # Insert Riwayat Kelas based on timeline
            if tingkat == '10':
                cursor.execute("""
                INSERT INTO riwayat_kelas (siswa_id, kelas_id, tahun_ajaran_id, spp_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'aktif', ?, ?)
                """, (siswa_id, kelas_id, ta_aktif_id, spp_id, now_str, now_str))
            elif tingkat == '11':
                prev_x = kelas_x_rpl_id if kelas_id == kelas_xi_rpl_id else kelas_x_tkj_id
                cursor.execute("""
                INSERT INTO riwayat_kelas (siswa_id, kelas_id, tahun_ajaran_id, spp_id, status, created_at, updated_at)
                VALUES (?, ?, 2, ?, 'naik', ?, ?)
                """, (siswa_id, prev_x, spp_tarifs['10'], now_str, now_str))
                cursor.execute("""
                INSERT INTO riwayat_kelas (siswa_id, kelas_id, tahun_ajaran_id, spp_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'aktif', ?, ?)
                """, (siswa_id, kelas_id, ta_aktif_id, spp_id, now_str, now_str))
            elif tingkat == '12':
                prev_x = kelas_x_rpl_id if kelas_id == kelas_xii_rpl_id else kelas_x_tkj_id
                prev_xi = kelas_xi_rpl_id if kelas_id == kelas_xii_rpl_id else kelas_xi_tkj_id
                cursor.execute("""
                INSERT INTO riwayat_kelas (siswa_id, kelas_id, tahun_ajaran_id, spp_id, status, created_at, updated_at)
                VALUES (?, ?, 1, ?, 'naik', ?, ?)
                """, (siswa_id, prev_x, spp_tarifs['10'], now_str, now_str))
                cursor.execute("""
                INSERT INTO riwayat_kelas (siswa_id, kelas_id, tahun_ajaran_id, spp_id, status, created_at, updated_at)
                VALUES (?, ?, 2, ?, 'naik', ?, ?)
                """, (siswa_id, prev_xi, spp_tarifs['11'], now_str, now_str))
                cursor.execute("""
                INSERT INTO riwayat_kelas (siswa_id, kelas_id, tahun_ajaran_id, spp_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'aktif', ?, ?)
                """, (siswa_id, kelas_id, ta_aktif_id, spp_id, now_str, now_str))

        return students

    # Kelas X: 53 siswa (X RPL 1: 27 siswa, X TKJ 1: 26 siswa)
    siswas_x_rpl = generate_students(27, '10', kelas_x_rpl_id, spp_tarifs['10'], 10001001, 26001, 2026)
    siswas_x_tkj = generate_students(26, '10', kelas_x_tkj_id, spp_tarifs['10'], 10002001, 26028, 2026)

    # Kelas XI: 56 siswa (XI RPL 1: 28 siswa, XI TKJ 1: 28 siswa)
    siswas_xi_rpl = generate_students(28, '11', kelas_xi_rpl_id, spp_tarifs['11'], 11001001, 25001, 2025)
    siswas_xi_tkj = generate_students(28, '11', kelas_xi_tkj_id, spp_tarifs['11'], 11002001, 25029, 2025)

    # Kelas XII: 52 siswa (XII RPL 1: 26 siswa, XII TKJ 1: 26 siswa)
    siswas_xii_rpl = generate_students(26, '12', kelas_xii_rpl_id, spp_tarifs['12'], 12001001, 24001, 2024)
    siswas_xii_tkj = generate_students(26, '12', kelas_xii_tkj_id, spp_tarifs['12'], 12002001, 24027, 2024)

    # 9. Government support demo
    # Mark 2 students as covered by government
    cursor.execute("""
    INSERT INTO siswa_tanggungan_pemerintah (siswa_id, spp_id, cakupan, tanggal_mulai, keterangan, created_at, updated_at)
    VALUES (?, ?, 'spp', '2026-07-01', 'Beasiswa Prestasi Kurang Mampu (BOS / Disdik)', ?, ?),
           (?, ?, 'semua', '2026-07-01', 'Bantuan Penuh Program Indonesia Pintar (PIP)', ?, ?);
    """, (siswas_x_rpl[5], spp_tarifs['10'], now_str, now_str,
          siswas_xi_rpl[10], spp_tarifs['11'], now_str, now_str))

    # 10. Seed Realistic Payments for Demo
    # Academic months: Juli, Agustus, September, Oktober, November
    months_ta = ['Juli', 'Agustus', 'September', 'Oktober', 'November']
    full_months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni']
    kode_counter = 1

    # Get non-spp tariffs per level
    non_spp_by_tingkat = {
        '10': [t for t in all_tarifs if t['tingkat'] == '10' and t['kategori'] != 'SPP'],
        '11': [t for t in all_tarifs if t['tingkat'] == '11' and t['kategori'] != 'SPP'],
        '12': [t for t in all_tarifs if t['tingkat'] == '12' and t['kategori'] != 'SPP'],
    }

    # Simulate realistic multi-year payments:
    # A. In Kelas XII:
    # First 12 students: Fully paid all fees across Grade 10, Grade 11, and Grade 12!
    # These 12 students are completely free of arrears (Bebas Tunggakan across 3-year timeline) -> Ready for Graduation demo!
    for s_id in siswas_xii_rpl[:12]:
        # Grade 10 SPP & Non-SPP (2024/2025)
        for b in full_months:
            thn = 2024 if b in ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] else 2025
            kode = f"SPP-2024{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2024-11-10', ?, ?, 100000, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, spp_tarifs['10'], b, thn, f"Pembayaran SPP Bulan {b}", now_str, now_str))
        for t in non_spp_by_tingkat['10']:
            kode = f"NSP-2024{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2024-09-15', NULL, 2024, ?, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, t['id'], t['nominal'], f"Pembayaran {t['nama']}", now_str, now_str))

        # Grade 11 SPP & Non-SPP (2025/2026)
        for b in full_months:
            thn = 2025 if b in ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] else 2026
            kode = f"SPP-2025{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2025-11-10', ?, ?, 100000, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, spp_tarifs['11'], b, thn, f"Pembayaran SPP Bulan {b}", now_str, now_str))
        for t in non_spp_by_tingkat['11']:
            kode = f"NSP-2025{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2025-09-15', NULL, 2025, ?, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, t['id'], t['nominal'], f"Pembayaran {t['nama']}", now_str, now_str))

        # Grade 12 SPP & Non-SPP (2026/2027)
        for b in full_months:
            thn = 2026 if b in ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] else 2027
            kode = f"SPP-2026{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2026-10-15', ?, ?, 100000, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, spp_tarifs['12'], b, thn, f"Pembayaran SPP Bulan {b}", now_str, now_str))
        for t in non_spp_by_tingkat['12']:
            kode = f"NSP-2026{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2026-10-20', NULL, 2026, ?, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, t['id'], t['nominal'], f"Pembayaran {t['nama']}", now_str, now_str))

    # Remaining students in Kelas XII: have realistic arrears from Grade 10, 11, or 12
    for i, s_id in enumerate(siswas_xii_rpl[12:] + siswas_xii_tkj):
        # Grade 10: paid 10-12 months SPP and most non-spp
        paid_x_months = 10 + (i % 3)
        for b in full_months[:paid_x_months]:
            thn = 2024 if b in ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] else 2025
            kode = f"SPP-2024{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2024-11-10', ?, ?, 100000, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, spp_tarifs['10'], b, thn, f"Pembayaran SPP Bulan {b}", now_str, now_str))
        # Grade 10 non-spp: pay all except Study Banding if i % 2 == 1
        for idx_t, t in enumerate(non_spp_by_tingkat['10']):
            if (i % 2 == 1) and ('Study Banding' in t['nama']):
                continue
            kode = f"NSP-2024{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2024-09-15', NULL, 2024, ?, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, t['id'], t['nominal'], f"Pembayaran {t['nama']}", now_str, now_str))

        # Grade 11: paid 9-11 months SPP
        paid_xi_months = 9 + (i % 3)
        for b in full_months[:paid_xi_months]:
            thn = 2025 if b in ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] else 2026
            kode = f"SPP-2025{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2025-11-10', ?, ?, 100000, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, spp_tarifs['11'], b, thn, f"Pembayaran SPP Bulan {b}", now_str, now_str))
        # Grade 11 non-spp: pay all except Prakerin if i % 2 == 0
        for idx_t, t in enumerate(non_spp_by_tingkat['11']):
            if (i % 2 == 0) and ('Prakerin' in t['nama']):
                continue
            kode = f"NSP-2025{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2025-09-15', NULL, 2025, ?, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, t['id'], t['nominal'], f"Pembayaran {t['nama']}", now_str, now_str))

        # Grade 12 (running year): paid 1-4 months SPP
        paid_month_count = (i % 4) + 1
        for b in months_ta[:paid_month_count]:
            kode = f"SPP-2026{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2026-09-10', ?, 2026, 100000, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, spp_tarifs['12'], b, f"Pembayaran SPP Bulan {b}", now_str, now_str))

        if i % 2 == 0 and non_spp_by_tingkat['12']:
            t = non_spp_by_tingkat['12'][0]
            kode = f"NSP-2026{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2026-09-12', NULL, 2026, ?, 'Transfer Bank', ?, ?, ?)
            """, (kode, petugas_id, s_id, t['id'], t['nominal'], f"Pembayaran {t['nama']}", now_str, now_str))

    # B. Kelas XI payments (Multi-year: Grade 10 completed + Grade 11 running)
    for i, s_id in enumerate(siswas_xi_rpl + siswas_xi_tkj):
        # Grade 10:
        if i % 2 == 0:
            # Fully paid Grade 10 SPP & Non-SPP
            for b in full_months:
                thn = 2025 if b in ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] else 2026
                kode = f"SPP-2025{kode_counter:05d}"
                kode_counter += 1
                cursor.execute("""
                INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
                VALUES (?, ?, ?, ?, '2025-10-15', ?, ?, 100000, 'Tunai', ?, ?, ?)
                """, (kode, petugas_id, s_id, spp_tarifs['10'], b, thn, f"Pembayaran SPP Bulan {b}", now_str, now_str))
            for t in non_spp_by_tingkat['10']:
                kode = f"NSP-2025{kode_counter:05d}"
                kode_counter += 1
                cursor.execute("""
                INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
                VALUES (?, ?, ?, ?, '2025-08-20', NULL, 2025, ?, 'Tunai', ?, ?, ?)
                """, (kode, petugas_id, s_id, t['id'], t['nominal'], f"Pembayaran {t['nama']}", now_str, now_str))
        else:
            # Partially paid Grade 10 (e.g. 10 months SPP, 1 non-spp unpaid)
            for b in full_months[:10]:
                thn = 2025 if b in ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] else 2026
                kode = f"SPP-2025{kode_counter:05d}"
                kode_counter += 1
                cursor.execute("""
                INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
                VALUES (?, ?, ?, ?, '2025-10-15', ?, ?, 100000, 'Tunai', ?, ?, ?)
                """, (kode, petugas_id, s_id, spp_tarifs['10'], b, thn, f"Pembayaran SPP Bulan {b}", now_str, now_str))
            for t in non_spp_by_tingkat['10'][:-1]: # skip last item (Kaos/Study Banding)
                kode = f"NSP-2025{kode_counter:05d}"
                kode_counter += 1
                cursor.execute("""
                INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
                VALUES (?, ?, ?, ?, '2025-08-20', NULL, 2025, ?, 'Tunai', ?, ?, ?)
                """, (kode, petugas_id, s_id, t['id'], t['nominal'], f"Pembayaran {t['nama']}", now_str, now_str))

        # Grade 11 (running year, 5 months: Juli - November)
        paid_count = ((i + 1) % 5)
        for b in months_ta[:paid_count]:
            kode = f"SPP-2026{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2026-09-05', ?, 2026, 100000, 'Transfer Bank', ?, ?, ?)
            """, (kode, petugas_id, s_id, spp_tarifs['11'], b, f"Pembayaran SPP Bulan {b}", now_str, now_str))

    # C. Kelas X payments (Grade 10 running year)
    for i, s_id in enumerate(siswas_x_rpl + siswas_x_tkj):
        paid_count = (i % 5)
        for b in months_ta[:paid_count]:
            kode = f"SPP-2026{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2026-08-15', ?, 2026, 100000, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, spp_tarifs['10'], b, f"Pembayaran SPP Bulan {b}", now_str, now_str))

        if i % 3 == 0 and len(non_spp_by_tingkat['10']) > 8:
            t = non_spp_by_tingkat['10'][8]
            kode = f"NSP-2026{kode_counter:05d}"
            kode_counter += 1
            cursor.execute("""
            INSERT INTO pembayarans (kode_transaksi, user_id, siswa_id, spp_id, tgl_bayar, bulan_dibayar, tahun_dibayar, jumlah_bayar, metode_pembayaran, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, '2026-08-20', NULL, 2026, ?, 'Tunai', ?, ?, ?)
            """, (kode, petugas_id, s_id, t['id'], t['nominal'], f"Pembayaran {t['nama']}", now_str, now_str))

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
    print(f"Total students: {len(siswas_x_rpl) + len(siswas_x_tkj) + len(siswas_xi_rpl) + len(siswas_xi_tkj) + len(siswas_xii_rpl) + len(siswas_xii_tkj)}")
    print(f"Total tariffs: {len(all_tarifs)}")

if __name__ == '__main__':
    seed()
