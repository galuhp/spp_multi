<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use UnitEnum;

class PanduanPenggunaan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static UnitEnum|string|null $navigationGroup = 'Bantuan';

    protected static ?string $navigationLabel = 'Buku Panduan';

    protected static ?string $title = 'Buku Panduan Penggunaan Sistem';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.panduan-penggunaan';

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

        $html = Str::markdown(
            $raw,
            [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'heading_permalink' => [
                    'id_prefix' => '',
                    'fragment_prefix' => '',
                    'apply_id_to_heading' => true,
                    'heading_class' => '',
                    'insert' => 'none',
                ],
                'slug_normalizer' => [
                    'max_length' => 255,
                ],
            ],
            [new HeadingPermalinkExtension],
        );

        return $this->formatCallouts($this->normalizeHeadingLevels($html));
    }

    /**
     * Keep heading hierarchy sequential inside the page (the page title is the only H1).
     */
    protected function normalizeHeadingLevels(string $html): string
    {
        return (string) preg_replace_callback(
            '/<(\/?)h([1-6])([^>]*)>/',
            function (array $matches): string {
                $level = (int) $matches[2];

                $level = match ($level) {
                    1 => 2,
                    2 => 3,
                    default => $level,
                };

                return '<'.$matches[1].'h'.$level.$matches[3].'>';
            },
            $html,
        );
    }

    /**
     * Turn GitHub style blockquote alerts into accessible, theme-aware callouts.
     */
    protected function formatCallouts(string $html): string
    {
        $callouts = [
            'IMPORTANT' => ['important', 'Penting', 'info'],
            'NOTE' => ['note', 'Catatan', 'info'],
            'TIP' => ['tip', 'Tips', 'bulb'],
            'WARNING' => ['warning', 'Peringatan', 'warning'],
            'CAUTION' => ['caution', 'Hati-hati', 'warning'],
        ];

        return (string) preg_replace_callback(
            '/<blockquote>\s*<p>\[!(IMPORTANT|NOTE|TIP|WARNING|CAUTION)\]\s*(.*?)<\/p>\s*<\/blockquote>/s',
            function (array $matches) use ($callouts): string {
                [$modifier, $label, $icon] = $callouts[$matches[1]];

                return sprintf(
                    '<blockquote class="panduan-alert panduan-alert-%1$s"><div class="panduan-alert-title">%2$s<span>%3$s</span></div><div class="panduan-alert-body">%4$s</div></blockquote>',
                    $modifier,
                    $this->calloutIcon($icon),
                    e($label),
                    $matches[2],
                );
            },
            $html,
        );
    }

    /**
     * Inline Heroicons SVG used by rendered callouts.
     */
    protected function calloutIcon(string $icon): string
    {
        $paths = [
            'info' => 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
            'warning' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
            'bulb' => 'M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18',
        ];

        return sprintf(
            '<svg class="panduan-alert-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="%s" /></svg>',
            $paths[$icon] ?? $paths['info'],
        );
    }

    /**
     * Chapters metadata for sidebar table of contents.
     *
     * @return array<int, array{id: string, judul: string, deskripsi: string, topik: string, icon: string}>
     */
    public function getDaftarBabProperty(): array
    {
        return [
            [
                'id' => 'bab-1-pengenalan--hak-akses-pengguna',
                'judul' => 'BAB 1: Pengenalan & Hak Akses',
                'deskripsi' => 'Peran pengguna, login SSO, dan manajemen izin modul.',
                'topik' => 'role admin petugas kasir wali kelas siswa login google sso sandi password izin hak akses modul',
                'icon' => 'shield-check',
            ],
            [
                'id' => 'bab-2-panduan-awal-tahun-ajaran-setup-master-data',
                'judul' => 'BAB 2: Setup Master Data',
                'deskripsi' => 'Profil sekolah, tahun ajaran, kelas, tarif & impor siswa.',
                'topik' => 'profil kop sekolah npsn kepala sekolah tahun ajaran jurusan kompetensi kelas rombel tarif spp biaya impor excel siswa',
                'icon' => 'building-library',
            ],
            [
                'id' => 'bab-3-transaksi-kasir--penerimaan-keuangan',
                'judul' => 'BAB 3: Kasir & Penerimaan SPP',
                'deskripsi' => 'POS kasir cepat, multi-bulan, kolektif, kuitansi & void.',
                'topik' => 'kasir pos pembayaran spp entri transaksi kolektif massal kuitansi qr code batal void',
                'icon' => 'banknotes',
            ],
            [
                'id' => 'bab-4-operasional-pengeluaran-kas-sekolah',
                'judul' => 'BAB 4: Pengeluaran Kas Sekolah',
                'deskripsi' => 'Belanja operasional, unggah nota, dan cetak voucher kas.',
                'topik' => 'pengeluaran kas belanja operasional nota faktur voucher bukti kas keluar rekap',
                'icon' => 'document-currency-dollar',
            ],
            [
                'id' => 'bab-5-subsidi-biaya--beasiswa-siswa',
                'judul' => 'BAB 5: Subsidi & Beasiswa',
                'deskripsi' => 'Diskon persentase (n%) dan pembebasan tanggungan pemerintah.',
                'topik' => 'subsidi diskon beasiswa pip bosp pemda ditanggung pemerintah persen potongan',
                'icon' => 'receipt-percent',
            ],
            [
                'id' => 'bab-6-siklus-akhir-tahun-ajaran-kelulusan--kenaikan-kelas',
                'judul' => 'BAB 6: Kelulusan & Kenaikan Kelas',
                'deskripsi' => 'Alur Top-Down, proteksi tunggakan, dan rollback batch.',
                'topik' => 'kelulusan kenaikan kelas top down rollback batch batal tinggal kelas akhir tahun',
                'icon' => 'academic-cap',
            ],
            [
                'id' => 'bab-7-laporan-keuangan-buku-besar--rekapitulasi',
                'judul' => 'BAB 7: Laporan & Buku Besar',
                'deskripsi' => 'Laporan kasir, tunggakan per kelas, potensi & audit trail.',
                'topik' => 'laporan keuangan buku besar kartu pembayaran tunggakan rekap potensi realisasi audit trail log aktivitas',
                'icon' => 'document-chart-bar',
            ],
            [
                'id' => 'bab-8-panduan-portal-mandiri-siswa--orang-tua',
                'judul' => 'BAB 8: Portal Mandiri Siswa',
                'deskripsi' => 'Pengecekan kartu tagihan 12 bulan dan cetak kuitansi mandiri.',
                'topik' => 'portal mandiri siswa orang tua nisn tagihan kartu spp 12 bulan riwayat pembayaran kuitansi',
                'icon' => 'device-phone-mobile',
            ],
            [
                'id' => 'bab-9-tanya-jawab-faq--solusi-permasalahan',
                'judul' => 'BAB 9: FAQ & Solusi Masalah',
                'deskripsi' => 'Penanganan kasir salah input, mutasi siswa, & panduan error.',
                'topik' => 'faq tanya jawab solusi masalah error salah input mutasi siswa pindahan impor gagal',
                'icon' => 'question-mark-circle',
            ],
        ];
    }
}
