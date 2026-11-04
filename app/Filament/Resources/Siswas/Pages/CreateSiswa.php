<?php

namespace App\Filament\Resources\Siswas\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Siswas\SiswaResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateSiswa extends CreateRecord
{
    protected static string $resource = SiswaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
