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

    public function mount(): void
    {
        parent::mount();

        // Support prefilling siswa_id from query string
        $siswaId = request()->query('siswa_id');
        if ($siswaId) {
            $siswa = Siswa::with('kelas')->find($siswaId);

            $this->form->fill([
                'jurusan_id' => $siswa?->kelas?->jurusan_id,
                'kelas_id' => $siswa?->kelas_id,
                'siswa_id' => (int) $siswaId,
                'jenis_transaksi' => 'spp',
                'tgl_bayar' => now()->format('Y-m-d'),
                'metode_pembayaran' => 'Tunai',
            ]);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $siswa = Siswa::with('spp')->findOrFail($data['siswa_id']);
        $kodeTransaksi = Pembayaran::generateKodeTransaksi();
        $userId = auth()->id();

        if (($data['jenis_transaksi'] ?? 'spp') === 'non_spp') {
            $tarif = Spp::findOrFail($data['spp_id']);

            return Pembayaran::create([
                'kode_transaksi' => $kodeTransaksi,
                'user_id' => $userId,
                'siswa_id' => $siswa->id,
                'spp_id' => $tarif->id,
                'tgl_bayar' => $data['tgl_bayar'],
                'bulan_dibayar' => null,
                'tahun_dibayar' => $tarif->tahun,
                'jumlah_bayar' => (int) $data['jumlah_bayar'],
                'metode_pembayaran' => $data['metode_pembayaran'],
                'catatan' => $data['catatan'] ?? null,
            ]);
        }

        // SPP Bulanan (Support Multi-bulan)
        $bulanList = (array) ($data['bulan_dibayar'] ?? []);
        if (empty($bulanList)) {
            Notification::make()
                ->title('Pilih minimal satu bulan SPP')
                ->danger()
                ->send();

            $this->halt();
        }

        $spp = $siswa->spp;
        $tahun = $spp?->tahun ?? (int) date('Y');
        $nominal = $spp?->nominal ?? 0;

        $created = [];
        DB::transaction(function () use ($bulanList, $kodeTransaksi, $userId, $siswa, $spp, $data, $tahun, $nominal, &$created) {
            foreach ($bulanList as $bulan) {
                $created[] = Pembayaran::create([
                    'kode_transaksi' => $kodeTransaksi,
                    'user_id' => $userId,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $spp?->id,
                    'tgl_bayar' => $data['tgl_bayar'],
                    'bulan_dibayar' => $bulan,
                    'tahun_dibayar' => $tahun,
                    'jumlah_bayar' => $nominal,
                    'metode_pembayaran' => $data['metode_pembayaran'],
                    'catatan' => $data['catatan'] ?? null,
                ]);
            }
        });

        return $created[0];
    }
}
