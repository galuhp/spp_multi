<?php

namespace App\Filament\Resources\Kelas\Schemas;

use App\Models\ProfilSekolah;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class KelasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tingkat')
                    ->label('Tingkat Kelas')
                    ->options(fn (): array => ProfilSekolah::tingkatKelasOptions())
                    ->searchable()
                    ->required(),
                TextInput::make('nama_kelas')
                    ->label('Nama Rombel Kelas')
                    ->placeholder('Contoh: X RPL 1 / IX-A / Kelas 1A')
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                            ->where('tahun_ajaran_id', $get('tahun_ajaran_id')),
                    )
                    ->validationMessages([
                        'unique' => 'Nama kelas sudah terdaftar.',
                    ])
                    ->required(),
                Select::make('jurusan_id')
                    ->label('Kompetensi Keahlian / Jurusan')
                    ->relationship('jurusan', 'nama')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran (Opsional / Kosongkan untuk Master Kelas Lintas Tahun)')
                    ->relationship('tahunAjaran', 'nama')
                    ->placeholder('Berlaku Lintas Tahun Ajaran (Master Rombel)')
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }
}
