<?php

namespace App\Filament\Resources\Pembayarans\Pages;

use App\Filament\Resources\Pembayarans\PembayaranResource;
use App\Models\Siswa;
use Filament\Resources\Pages\EditRecord;

class EditPembayaran extends EditRecord
{
    protected static string $resource = PembayaranResource::class;

    /**
     * Prefill the cascading major/class selects from the payment's student.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $siswa = Siswa::with('kelas')->find($data['siswa_id'] ?? null);

        $data['cara_pilih'] = 'bertingkat';
        $data['kelas_id'] = $siswa?->kelas_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
