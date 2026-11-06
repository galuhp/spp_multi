<?php

namespace App\Filament\Resources\Pembayarans\Pages;

use App\Filament\Resources\Pembayarans\PembayaranResource;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use App\Support\Pembayaran\TanggunganPemerintahService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreatePembayaran extends CreateRecord
{
    protected static string $resource = PembayaranResource::class;

    public function getTitle(): string
    {
        return 'Entri Pembayaran';
    }

    public function mount(): void
    {
        parent::mount();

        $siswaId = request()->query('siswa_id');
        $kelasId = request()->query('kelas_id');

        if ($siswaId) {
            $siswa = Siswa::with('kelas')->find($siswaId);

            $this->form->fill([
                'cara_pilih' => 'cari',
                'kelas_id' => $siswa?->kelas_id,
                'siswa_id' => (int) $siswaId,
                'sertakan_arsip' => false,
                'bayar_spp' => ['spp'],
                'bayar_non_spp' => [],
                'tgl_bayar' => now()->format('Y-m-d'),
                'metode_pembayaran' => 'Tunai',
            ]);

            return;
        }

        if ($kelasId) {
            $this->form->fill([
                'cara_pilih' => 'bertingkat',
                'kelas_id' => (int) $kelasId,
                'sertakan_arsip' => request()->boolean('sertakan_arsip'),
                'bayar_spp' => ['spp'],
                'bayar_non_spp' => [],
                'tgl_bayar' => now()->format('Y-m-d'),
                'metode_pembayaran' => 'Tunai',
            ]);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        return Cache::lock(
            'pembayaran-siswa:'.((int) ($data['siswa_id'] ?? 0)),
            10,
        )->block(5, fn (): Model => $this->handleRecordCreationTerlindungi($data));
    }

    protected function handleRecordCreationTerlindungi(array $data): Model
    {
        $pilihSpp = in_array('spp', (array) ($data['bayar_spp'] ?? []), true);
        $pilihNonSpp = in_array('non_spp', (array) ($data['bayar_non_spp'] ?? []), true);

        if (! $pilihSpp && ! $pilihNonSpp) {
            $this->tolak('Pilih minimal satu jenis pembayaran.');
        }

        $siswa = Siswa::with([
            'spp',
            'pembayarans' => fn ($query) => $query->whereNull('void_at'),
        ])->findOrFail($data['siswa_id']);

        $kodeTransaksi = Pembayaran::generateKodeTransaksi();
        $userId = auth()->id();

        $bulanList = $pilihSpp ? array_values((array) ($data['bulan_dibayar'] ?? [])) : [];

        if ($pilihSpp && $bulanList === []) {
            $this->tolak('Pilih minimal satu bulan SPP.');
        }

        $spp = $siswa->spp;
        $tahunMulai = Pembayaran::tahunMulaiTarif($spp);
        $nominalSpp = (int) ($spp?->nominal ?? 0);
        $nominalPerBulan = [];
        $tanggunganPemerintah = app(TanggunganPemerintahService::class);

        if ($pilihSpp && ($spp === null || $nominalSpp <= 0)) {
            $this->tolak('Siswa belum memiliki tarif SPP yang valid.');
        }

        if ($pilihSpp) {
            $dibayarPerBulan = $siswa->pembayarans
                ->where('spp_id', $spp?->id)
                ->whereNotNull('bulan_dibayar')
                ->groupBy('bulan_dibayar')
                ->map(fn ($rows): int => (int) $rows->sum('jumlah_bayar'));

            $bulanLunas = [];
            $bulanDitanggung = [];

            foreach ($bulanList as $bulan) {
                if ($tanggunganPemerintah->isSppDitanggung($siswa, $spp, $bulan, Pembayaran::tahunUntukBulan($bulan, $tahunMulai))) {
                    $bulanDitanggung[] = $bulan;

                    continue;
                }

                $dibayar = (int) ($dibayarPerBulan->get($bulan) ?? 0);
                $sisa = max(0, $nominalSpp - $dibayar);

                if ($sisa <= 0) {
                    $bulanLunas[] = $bulan;

                    continue;
                }

                $nominalPerBulan[$bulan] = $sisa;
            }

            if ($bulanDitanggung !== []) {
                $this->tolak('Bulan SPP berikut ditanggung pemerintah: '.implode(', ', $bulanDitanggung).'.');
            }

            if ($bulanLunas !== []) {
                $this->tolak('Bulan SPP berikut sudah lunas: '.implode(', ', $bulanLunas).'.');
            }
        }

        $nonSppIds = collect($pilihNonSpp ? ($data['non_spp_ids'] ?? []) : [])
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($pilihNonSpp && $nonSppIds->isEmpty()) {
            $this->tolak('Pilih minimal satu pos biaya lainnya.');
        }

        $validatedNonSpp = [];

        foreach ($nonSppIds as $tarifId) {
            $tarif = Spp::find($tarifId);

            if (! $tarif || $tarif->kategori === Spp::KATEGORI_SPP) {
                $this->tolak('Pos biaya yang dipilih tidak valid.');
            }

            if ($tanggunganPemerintah->isPosDitanggung($siswa, $tarif)) {
                $this->tolak("Tagihan {$tarif->kategori} ditanggung pemerintah.");
            }

            $dibayar = (int) $siswa->pembayarans->where('spp_id', $tarif->id)->sum('jumlah_bayar');
            $sisa = max(0, (int) $tarif->nominal - $dibayar);

            if ($sisa <= 0) {
                $this->tolak("Tagihan {$tarif->kategori} sudah lunas.");
            }

            $validatedNonSpp[] = ['tarif' => $tarif, 'jumlah' => $sisa];
        }

        $created = [];

        DB::transaction(function () use ($pilihSpp, $bulanList, $kodeTransaksi, $userId, $siswa, $spp, $data, $tahunMulai, $nominalSpp, $nominalPerBulan, $validatedNonSpp, &$created) {
            if ($pilihSpp) {
                foreach ($bulanList as $bulan) {
                    $created[] = Pembayaran::create([
                        'kode_transaksi' => $kodeTransaksi,
                        'user_id' => $userId,
                        'siswa_id' => $siswa->id,
                        'spp_id' => $spp?->id,
                        'tgl_bayar' => $data['tgl_bayar'],
                        'bulan_dibayar' => $bulan,
                        'tahun_dibayar' => Pembayaran::tahunUntukBulan($bulan, $tahunMulai),
                        'jumlah_bayar' => $nominalPerBulan[$bulan] ?? $nominalSpp,
                        'metode_pembayaran' => $data['metode_pembayaran'],
                        'catatan' => $data['catatan'] ?? null,
                    ]);
                }
            }

            foreach ($validatedNonSpp as $item) {
                $created[] = Pembayaran::create([
                    'kode_transaksi' => $kodeTransaksi,
                    'user_id' => $userId,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $item['tarif']->id,
                    'tgl_bayar' => $data['tgl_bayar'],
                    'bulan_dibayar' => null,
                    'tahun_dibayar' => $item['tarif']->tahun,
                    'jumlah_bayar' => $item['jumlah'],
                    'metode_pembayaran' => $data['metode_pembayaran'],
                    'catatan' => $data['catatan'] ?? null,
                ]);
            }
        });

        if ($created === []) {
            $this->tolak('Tidak ada pembayaran yang dapat disimpan.');
        }

        return $created[0];
    }

    protected function tolak(string $pesan): void
    {
        Notification::make()
            ->title($pesan)
            ->danger()
            ->send();

        $this->halt();
    }
}
