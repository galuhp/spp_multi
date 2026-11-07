<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Segmen Paket Aplikasi (Basic vs Pro)
    |--------------------------------------------------------------------------
    |
    | Nilai yang didukung: "basic", "pro"
    | Seluruh fitur yang ada pada rilis saat ini masuk ke dalam paket "basic".
    | Fitur-fitur pada pengembangan selanjutnya (next build) dapat dikunci
    | ke segmen "pro" menggunakan trait `RequiresProTier`, helper
    | `LisensiPaket::isPro()`, directive `@pro`, atau middleware `pro`.
    |
    */
    'tier' => env('APP_TIER', 'basic'),

    /*
    |--------------------------------------------------------------------------
    | Tampilkan Menu Pro yang Terkunci di Sidebar
    |--------------------------------------------------------------------------
    |
    | Jika disetel true pada paket Basic, halaman/menu bertanda `RequiresProTier`
    | tetap ditampilkan di sidebar dengan badge "PRO" namun tidak bisa diakses.
    | Jika false, menu khusus Pro disembunyikan sepenuhnya pada paket Basic.
    |
    */
    'show_locked_pro_navigation' => (bool) env('APP_SHOW_LOCKED_PRO_NAV', false),

    /*
    |--------------------------------------------------------------------------
    | Daftar Feature Flag per Segmen Paket
    |--------------------------------------------------------------------------
    |
    | Petakan kode fitur ke minimum paket yang dibutuhkan ('basic' atau 'pro').
    | Tambahkan kunci fitur baru di sini pada next build untuk membatasi fitur
    | tertentu hanya untuk pelanggan paket Pro.
    |
    */
    'features' => [
        // Fitur eksisting (tersedia di Basic & Pro)
        'pembayaran_kasir' => 'basic',
        'pembayaran_kolektif' => 'basic',
        'tanggungan_pemerintah' => 'basic',
        'laporan_standar' => 'basic',
        'kenaikan_dan_kelulusan' => 'basic',

        // Tempatkan fitur-fitur Pro berikutnya (Next Build) di bawah ini:
        // 'whatsapp_notifikasi' => 'pro',
        // 'payment_gateway' => 'pro',
        // 'analitik_lanjutan' => 'pro',
    ],
];
