<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use UnitEnum;

class PanduanPenggunaan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static UnitEnum|string|null $navigationGroup = 'Bantuan';

    protected static ?string $navigationLabel = 'Buku Panduan';

    protected static ?string $title = 'Buku Panduan Penggunaan Sistem';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.panduan-penggunaan';

    public string $cari = '';

    /**
     * Determine if user has access to view user guide.
     */
    public static function canAccess(): bool
    {
        return auth()->check();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak')
                ->label('Cetak Panduan')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->extraAttributes([
                    'onclick' => 'window.print()',
                ]),
            Action::make('unduh')
                ->label('Unduh Dokumen (.md)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->url(fn (): string => route('panduan.unduh'))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Get the HTML rendered from Markdown documentation.
     */
    public function getKontenHtmlProperty(): string
    {
        $path = base_path('docs/PANDUAN_PENGGUNAAN.md');

        if (! file_exists($path)) {
            return '<p class="text-rose-500 font-semibold">Berkas dokumentasi docs/PANDUAN_PENGGUNAAN.md belum tersedia.</p>';
        }

        $raw = (string) file_get_contents($path);

        return Str::markdown($raw);
    }

    /**
     * Chapters metadata for sidebar table of contents.
     *
     * @return array<int, array{id: string, judul: string, deskripsi: string, icon: string}>
     */
    public function getDaftarBabProperty(): array
    {
        return [
            [
                'id' => 'bab-1-pengenalan--hak-akses-pengguna',
                'judul' => 'BAB 1: Pengenalan & Hak Akses',
                'deskripsi' => 'Peran pengguna, login SSO, dan manajemen izin modul.',
                'icon' => 'shield-check',
            ],
            [
                'id' => 'bab-2-panduan-awal-tahun-ajaran-setup-master-data',
                'judul' => 'BAB 2: Setup Master Data',
                'deskripsi' => 'Profil sekolah, tahun ajaran, kelas, tarif & impor siswa.',
                'icon' => 'building-library',
            ],
            [
                'id' => 'bab-3-transaksi-kasir--penerimaan-keuangan',
                'judul' => 'BAB 3: Kasir & Penerimaan SPP',
                'deskripsi' => 'POS kasir cepat, multi-bulan, kolektif, kuitansi & void.',
                'icon' => 'banknotes',
            ],
            [
                'id' => 'bab-4-operasional-pengeluaran-kas-sekolah',
                'judul' => 'BAB 4: Pengeluaran Kas Sekolah',
                'deskripsi' => 'Belanja operasional, unggah nota, dan cetak voucher kas.',
                'icon' => 'document-currency-dollar',
            ],
            [
                'id' => 'bab-5-subsidi-biaya--beasiswa-siswa',
                'judul' => 'BAB 5: Subsidi & Beasiswa',
                'deskripsi' => 'Diskon persentase (n%) dan pembebasan tanggungan pemerintah.',
                'icon' => 'receipt-percent',
            ],
            [
                'id' => 'bab-6-siklus-akhir-tahun-ajaran-kelulusan--kenaikan-kelas',
                'judul' => 'BAB 6: Kelulusan & Kenaikan Kelas',
                'deskripsi' => 'Alur Top-Down, proteksi tunggakan, dan rollback batch.',
                'icon' => 'academic-cap',
            ],
            [
                'id' => 'bab-7-laporan-keuangan-buku-besar--rekapitulasi',
                'judul' => 'BAB 7: Laporan & Buku Besar',
                'deskripsi' => 'Laporan kasir, tunggakan per kelas, potensi & audit trail.',
                'icon' => 'document-chart-bar',
            ],
            [
                'id' => 'bab-8-panduan-portal-mandiri-siswa--orang-tua',
                'judul' => 'BAB 8: Portal Mandiri Siswa',
                'deskripsi' => 'Pengecekan kartu tagihan 12 bulan dan cetak kuitansi mandiri.',
                'icon' => 'device-phone-mobile',
            ],
            [
                'id' => 'bab-9-tanya-jawab-faq--solusi-permasalahan',
                'judul' => 'BAB 9: FAQ & Solusi Masalah',
                'deskripsi' => 'Penanganan kasir salah input, mutasi siswa, & panduan error.',
                'icon' => 'question-mark-circle',
            ],
        ];
    }
}
