<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\Kelas;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $selected = $this->data['kelas_wali_ids'] ?? [];
        if ($this->record->role === UserRole::WaliKelas && ! empty($selected)) {
            Kelas::whereIn('id', $selected)->update(['wali_kelas_id' => $this->record->id]);
        }
    }
}
