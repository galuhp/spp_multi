<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\Kelas;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['kelas_wali_ids'] = $this->record->kelasWali()->pluck('id')->all();

        return $data;
    }

    protected function afterSave(): void
    {
        $selected = $this->data['kelas_wali_ids'] ?? [];

        if ($this->record->role === UserRole::WaliKelas) {
            Kelas::where('wali_kelas_id', $this->record->id)
                ->whereNotIn('id', $selected)
                ->update(['wali_kelas_id' => null]);

            if (! empty($selected)) {
                Kelas::whereIn('id', $selected)
                    ->update(['wali_kelas_id' => $this->record->id]);
            }
        } else {
            Kelas::where('wali_kelas_id', $this->record->id)
                ->update(['wali_kelas_id' => null]);
        }
    }
}
