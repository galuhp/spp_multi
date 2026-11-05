<?php

namespace App\Filament\Resources\Siswas\Pages;

use App\Filament\Resources\Siswas\SiswaResource;
use App\Models\Kelas;
use App\Models\Spp;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSiswa extends EditRecord
{
    protected static string $resource = SiswaResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $kelas = Kelas::with('tahunAjaran')->find($data['kelas_id'] ?? null);
        $data['spp_id'] = Spp::untukKelas($kelas)?->id ?? ($data['spp_id'] ?? null);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
