<?php

namespace App\Filament\Resources\Siswas\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Siswas\SiswaResource;
use App\Models\Kelas;
use App\Models\Spp;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateSiswa extends CreateRecord
{
    protected static string $resource = SiswaResource::class;

    public ?string $kataSandiAwal = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $kelas = Kelas::with('tahunAjaran')->find($data['kelas_id'] ?? null);
        $data['spp_id'] = Spp::untukKelas($kelas)?->id ?? ($data['spp_id'] ?? null);

        $this->kataSandiAwal = Str::password(12);

        $user = User::create([
            'name' => $data['nama'],
            'username' => $data['nisn'],
            'password' => Hash::make($this->kataSandiAwal),
            'role' => UserRole::Siswa,
        ]);

        $data['user_id'] = $user->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->kataSandiAwal) {
            return;
        }

        Notification::make()
            ->title('Akun siswa dibuat')
            ->body("Username: {$this->record->nisn}. Kata sandi awal: {$this->kataSandiAwal}. Minta siswa segera mengganti kata sandi.")
            ->warning()
            ->persistent()
            ->send();
    }
}
