<?php

namespace App\Filament\Resources\TahunAjarans\Schemas;

use App\Models\TahunAjaran;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TahunAjaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Tahun Ajaran')
                    ->placeholder('2026/2027')
                    ->helperText('Gunakan format YYYY/YYYY, contoh 2026/2027.')
                    ->required()
                    ->maxLength(20)
                    ->regex('/^\d{4}\/\d{4}$/')
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'regex' => 'Format tahun ajaran harus YYYY/YYYY, contoh 2026/2027.',
                        'unique' => 'Tahun ajaran ini sudah terdaftar.',
                    ]),
                DatePicker::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                DatePicker::make('tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('tanggal_mulai')
                    ->validationMessages([
                        'after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
                    ]),
                Toggle::make('aktif')
                    ->label('Tahun ajaran aktif')
                    ->helperText('Hanya satu tahun ajaran yang dapat aktif. Mengaktifkan tahun ini akan menonaktifkan tahun ajaran lain.')
                    ->default(fn (): bool => TahunAjaran::query()->where('aktif', true)->doesntExist()),
            ]);
    }
}
