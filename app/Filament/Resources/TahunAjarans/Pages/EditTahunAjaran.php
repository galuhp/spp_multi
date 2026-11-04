<?php

namespace App\Filament\Resources\TahunAjarans\Pages;

use App\Filament\Resources\TahunAjarans\TahunAjaranResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTahunAjaran extends EditRecord
{
    protected static string $resource = TahunAjaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (): bool => $this->getRecord()->sedangDipakai())
                ->tooltip(fn (): ?string => $this->getRecord()->sedangDipakai()
                    ? 'Tidak dapat dihapus karena masih dipakai kelas atau riwayat kelas.'
                    : null),
        ];
    }
}
