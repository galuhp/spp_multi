<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(100),

                TextInput::make('username')
                    ->label('Username / ID Login')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),

                TextInput::make('email')
                    ->label('Alamat Email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),

                Select::make('role')
                    ->label('Hak Akses (Role)')
                    ->options([
                        'admin' => 'Administrator',
                        'petugas' => 'Petugas TU / Kasir',
                        'wali_kelas' => 'Wali Kelas',
                        'siswa' => 'Siswa',
                    ])
                    ->live()
                    ->default('petugas')
                    ->required(),

                Select::make('kelas_wali_ids')
                    ->label('Kelas Binaan (Wali Kelas)')
                    ->multiple()
                    ->options(fn (): array => \App\Models\Kelas::query()->orderBy('nama_kelas')->pluck('nama_kelas', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('role') === 'wali_kelas')
                    ->helperText('Tentukan kelas yang dibina oleh akun wali kelas ini.'),

                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->maxLength(255)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Minimal 8 karakter. Kosongkan jika tidak ingin mengubah kata sandi.'),
            ]);
    }
}
