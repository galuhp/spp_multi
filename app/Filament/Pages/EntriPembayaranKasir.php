<?php

namespace App\Filament\Pages;

use App\Models\Pembayaran;
use App\Models\ProfilSekolah;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\Spp;
use App\Support\Pembayaran\PembayaranService;
use App\Support\Pembayaran\TanggunganPemerintahService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;
use UnitEnum;

class EntriPembayaranKasir extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Transaksi SPP';

    protected static ?string $navigationLabel = 'Entry Pembayaran 2';

    protected static ?string $title = 'Entry Pembayaran 2';

    protected static ?string $slug = 'entry-pembayaran-2';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.entri-pembayaran-kasir';

    public ?int $selectedSiswaId = null;

    public string $cari = '';

    public string $tingkat = 'semua';

    public ?string $galat = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::TRANSAKSI_SPP);
    }

    public function mount(): void
    {
        $siswaId = request()->integer('siswa_id');

        if ($siswaId && Siswa::query()->aktif()->whereKey($siswaId)->exists()) {
            $this->selectedSiswaId = $siswaId;
        }
    }

    /**
     * @return array<string, string>
     */
    public function getTingkatOptionsProperty(): array
    {
        return ['semua' => 'Semua'] + ProfilSekolah::tingkatSppOptions();
    }

    /**
     * @return Collection<int, Siswa>
     */
    public function getSiswaHasilProperty(): Collection
    {
        $kataKunci = trim($this->cari);

        if (mb_strlen($kataKunci) < 2) {
            return collect();
        }

        return Siswa::query()
            ->with('kelas')
            ->aktif()
            ->when($this->tingkat !== 'semua', function ($query): void {
                $labelTingkat = Spp::labelTingkat($this->tingkat);

                $query->whereHas('kelas', function ($kelasQuery) use ($labelTingkat): void {
                    $kelasQuery->whereIn('tingkat', array_values(array_unique([
                        $this->tingkat,
                        $labelTingkat,
                        mb_strtolower($labelTingkat),
                    ])));
                });
            })
            ->where(function ($query) use ($kataKunci): void {
                $query->where('nama', 'like', "%{$kataKunci}%")
                    ->orWhere('nisn', 'like', "%{$kataKunci}%")
                    ->orWhere('nis', 'like', "%{$kataKunci}%");
            })
            ->orderBy('nama')
            ->limit(20)
            ->get();
    }

    public function pilihTingkat(string $tingkat): void
    {
        if (! array_key_exists($tingkat, $this->tingkatOptions)) {
            return;
        }

        $this->tingkat = $tingkat;
        $this->cari = '';
        $this->dispatch('focus-cari');
    }

    protected ?Siswa $cachedSiswa = null;

    protected ?int $cachedSiswaId = null;

    public function getSiswaProperty(): ?Siswa
    {
        if (! $this->selectedSiswaId) {
            return null;
        }

        if ($this->cachedSiswaId === $this->selectedSiswaId && $this->cachedSiswa !== null) {
            return $this->cachedSiswa;
        }

        $this->cachedSiswaId = $this->selectedSiswaId;

        return $this->cachedSiswa = Siswa::query()
            ->with(['kelas.jurusan', 'spp', 'pembayarans'])
            ->aktif()
            ->find($this->selectedSiswaId);
    }

    /**
     * Data for the Alpine cashier component.
     *
     * @return array<string, mixed>
     */
    public function getKasirDataProperty(): array
    {
        $siswa = $this->siswa;

        if (! $siswa) {
            return [
                'tarif' => 0,
                'months' => [],
                'non_spp' => [],
                'tanggal' => now()->toDateString(),
            ];
        }

        $service = app(PembayaranService::class);
        $tanggunganPemerintah = app(TanggunganPemerintahService::class);
        $items = collect($service->tagihanTerbuka($siswa))->keyBy('key');
        $spp = $siswa->spp;
        $tahunMulai = Pembayaran::tahunMulaiTarif($spp);
        $nominal = (int) ($spp?->nominal ?? 0);

        $tahunAjaranSekarang = now()->month >= 7 ? now()->year : now()->year - 1;
        $indexSekarang = (now()->month + 5) % 12;

        $months = [];

        // Tunggakan SPP tahun-tahun ajaran sebelumnya (timeline riwayat kelas)
        foreach ($items->where('tipe', 'spp') as $unpaidSpp) {
            if ((int) $unpaidSpp['tahun'] < $tahunAjaranSekarang) {
                $months[] = [
                    'key' => $unpaidSpp['key'],
                    'bulan' => $unpaidSpp['bulan'],
                    'tahun' => $unpaidSpp['tahun'],
                    'nominal' => (int) $unpaidSpp['sisa'],
                    'status' => 'tunggakan',
                ];
            }
        }

        if ($spp?->isBulanan()) {
            foreach (Pembayaran::bulanTahunAjaran($tahunAjaranSekarang) as $index => $periode) {
                $key = "spp:{$spp->id}:{$periode['bulan']}:{$periode['tahun']}";
                $terbuka = $items->get($key);

                if (! $terbuka) {
                    $status = $tanggunganPemerintah->isSppDitanggung($siswa, $spp, $periode['bulan'], $periode['tahun'])
                        ? 'ditanggung'
                        : 'lunas';
                } elseif ($index < $indexSekarang) {
                    $status = 'tunggakan';
                } elseif ($index === $indexSekarang) {
                    $status = 'berjalan';
                } else {
                    $status = 'akan';
                }

                $months[] = [
                    'key' => $key,
                    'bulan' => $periode['bulan'],
                    'tahun' => $periode['tahun'],
                    'nominal' => $terbuka ? (int) $terbuka['sisa'] : $nominal,
                    'status' => $status,
                ];
            }
        }

        $nonSppItems = collect($items)->where('tipe', 'non_spp');
        $tarifNonSpp = Spp::query()
            ->whereIn('id', $nonSppItems->pluck('spp_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $nonSpp = $nonSppItems
            ->map(function (array $item) use ($tarifNonSpp): array {
                $tarif = $tarifNonSpp->get($item['spp_id']);
                $labelTingkat = $tarif?->tingkat ? ' (Kelas '.Spp::labelTingkat($tarif->tingkat).')' : '';
                $labelBulan = filled($item['bulan'] ?? null) ? " — {$item['bulan']} {$item['tahun']}" : '';
                $bulanan = ($tarif?->tipe ?? Spp::TIPE_SEKALI_BAYAR) === Spp::TIPE_BULANAN;

                return [
                    'key' => $item['key'],
                    'nama' => ($tarif?->nama ?: ($tarif?->kategori ?: 'Biaya Lainnya')).$labelBulan.$labelTingkat,
                    'deskripsi' => $tarif?->kategori ?: 'Sisa tagihan',
                    'sisa' => (int) $item['sisa'],
                    'grup' => $bulanan ? 'bulanan' : 'sekali_bayar',
                ];
            })
            ->sortBy(fn (array $item): int => $item['grup'] === 'bulanan' ? 0 : 1)
            ->values()
            ->all();

        return [
            'tarif' => $nominal,
            'months' => $months,
            'non_spp' => $nonSpp,
            'tanggal' => now()->toDateString(),
        ];
    }

    public function pilihSiswa(int $siswaId): void
    {
        $siswa = Siswa::query()->aktif()->find($siswaId);

        if (! $siswa) {
            $this->galat = 'Siswa tidak ditemukan atau tidak aktif.';

            return;
        }

        $this->selectedSiswaId = $siswa->id;
        $this->cari = '';
        $this->galat = null;
    }

    public function gantiSiswa(): void
    {
        $this->selectedSiswaId = null;
        $this->cari = '';
        $this->galat = null;
    }

    /**
     * @param  array<int, string>  $bulanKeys
     * @param  array<int, string>  $nonSppKeys
     */
    public function simpan(
        array $bulanKeys = [],
        array $nonSppKeys = [],
        string $metode = 'Tunai',
        ?string $tanggal = null,
        ?string $catatan = null,
    ): void {
        $siswa = $this->siswa;

        if (! $siswa) {
            $this->tolak('Pilih siswa terlebih dahulu.');

            return;
        }

        $items = collect(app(PembayaranService::class)->tagihanTerbuka($siswa))->keyBy('key');
        $alokasi = [];
        $total = 0;
        $tingkat = Spp::normalisasiTingkat($siswa->kelas?->tingkat);

        foreach (array_unique($bulanKeys) as $key) {
            $item = $items->get($key);

            if (! $item || $item['tipe'] !== 'spp') {
                $this->tolak('Pilihan bulan SPP tidak valid. Muat ulang halaman.');

                return;
            }

            $alokasi[] = ['tagihan' => $key, 'nominal' => (int) $item['sisa']];
            $total += (int) $item['sisa'];
        }

        foreach (array_unique($nonSppKeys) as $key) {
            $item = $items->get($key);

            if (! $item || $item['tipe'] !== 'non_spp') {
                $this->tolak('Pilihan biaya lain tidak valid. Muat ulang halaman.');

                return;
            }

            $tarif = Spp::query()->find($item['spp_id']);
            $tarifTingkat = Spp::normalisasiTingkat($tarif?->tingkat);

            if ($tarif && $tarifTingkat !== null && $tarifTingkat !== $tingkat) {
                $this->tolak("Biaya {$tarif->kategori} tidak berlaku untuk tingkat siswa ini.");

                return;
            }

            $alokasi[] = ['tagihan' => $key, 'nominal' => (int) $item['sisa']];
            $total += (int) $item['sisa'];
        }

        if ($alokasi === []) {
            $this->tolak('Pilih minimal satu tagihan yang akan dibayar.');

            return;
        }

        $metode = in_array($metode, ['Tunai', 'Transfer Bank', 'QRIS'], true) ? $metode : 'Tunai';
        $tanggal = $tanggal ?: now()->toDateString();

        try {
            $dibuat = DB::transaction(fn (): array => app(PembayaranService::class)->buatPembayaran(
                $siswa,
                $total,
                $alokasi,
                $metode,
                $tanggal,
                $catatan,
                (int) auth()->id(),
            ));

            $this->redirect(route('pembayaran.cetak', $dibuat[0]));
        } catch (Throwable $exception) {
            $this->tolak($exception->getMessage());
        }
    }

    protected function tolak(string $pesan): void
    {
        Notification::make()
            ->title('Pembayaran gagal')
            ->body($pesan)
            ->danger()
            ->send();
    }
}
