<?php

namespace App\Filament\Resources\Siswas\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Siswas\SiswaResource;
use App\Models\Kelas;
use App\Models\Spp;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateSiswa extends CreateRecord
{
    protected static string $resource = SiswaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $kelas = Kelas::with('tahunAjaran')->find($data['kelas_id'] ?? null);
        $data['spp_id'] = Spp::untukKelas($kelas)?->id ?? ($data['spp_id'] ?? null);

        $user = User::create([
            'name' => $data['nama'],
            'username' => $data['nisn'],
            'password' => bcrypt($data['nisn']),
            'role' => UserRole::Siswa,
        ]);

        $data['user_id'] = $user->id;

        return $data;
    }
}
