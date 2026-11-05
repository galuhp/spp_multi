<?php

namespace App\Filament\Resources\Pembayarans\Pages;

use App\Filament\Resources\Pembayarans\PembayaranResource;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
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
        $pilihSpp = in_array('spp', (array) ($data['bayar_spp'] ?? []), true);
        $pilihNonSpp = in_array('non_spp', (array) ($data['bayar_non_spp'] ?? []), true);

        if (! $pilihSpp && ! $pilihNonSpp) {
            $this->tolak('Pilih minimal satu jenis pembayaran.');
        }

        $siswa = Siswa::with(['spp', 'pembayarans'])->findOrFail($data['siswa_id']);
        $kodeTransaksi = Pembayaran::generateKodeTransaksi();
        $userId = auth()->id();

        $bulanList = $pilihSpp ? array_values((array) ($data['bulan_dibayar'] ?? [])) : [];

        if ($pilihSpp && $bulanList === []) {
            $this->tolak('Pilih minimal satu bulan SPP.');
        }

        $spp = $siswa->spp;
        $tahunMulai = Pembayaran::tahunMulaiTarif($spp);
        $nominalSpp = (int) ($spp?->nominal ?? 0);

        if ($pilihSpp && ($spp === null || $nominalSpp <= 0)) {
            $this->tolak('Siswa belum memiliki tarif SPP yang valid.');
        }

        if ($pilihSpp) {
            $sudahDibayar = $siswa->pembayarans
                ->where('spp_id', $spp?->id)
                ->whereIn('bulan_dibayar', $bulanList)
                ->pluck('bulan_dibayar')
                ->all();

            if ($sudahDibayar !== []) {
                $this->tolak('Bulan SPP berikut sudah dibayar: '.implode(', ', $sudahDibayar).'.');
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

            $dibayar = (int) $siswa->pembayarans->where('spp_id', $tarif->id)->sum('jumlah_bayar');
            $sisa = max(0, (int) $tarif->nominal - $dibayar);

            if ($sisa <= 0) {
                $this->tolak("Tagihan {$tarif->kategori} sudah lunas.");
            }

            $validatedNonSpp[] = ['tarif' => $tarif, 'jumlah' => $sisa];
        }

        $created = [];

        DB::transaction(function () use ($pilihSpp, $bulanList, $kodeTransaksi, $userId, $siswa, $spp, $data, $tahunMulai, $nominalSpp, $validatedNonSpp, &$created) {
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
                        'jumlah_bayar' => $nominalSpp,
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
