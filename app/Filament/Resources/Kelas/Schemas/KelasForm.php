<?php

namespace App\Filament\Resources\Kelas\Schemas;

use App\Models\TahunAjaran;
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
                    ->options([
                        '1' => 'Kelas 1 (I - SD)',
                        '2' => 'Kelas 2 (II - SD)',
                        '3' => 'Kelas 3 (III - SD)',
                        '4' => 'Kelas 4 (IV - SD)',
                        '5' => 'Kelas 5 (V - SD)',
                        '6' => 'Kelas 6 (VI - SD)',
                        '7' => 'Kelas 7 (VII - SMP)',
                        '8' => 'Kelas 8 (VIII - SMP)',
                        '9' => 'Kelas 9 (IX - SMP)',
                        'X' => 'Kelas X (10 - SMA/SMK)',
                        'XI' => 'Kelas XI (11 - SMA/SMK)',
                        'XII' => 'Kelas XII (12 - SMA/SMK)',
                    ])
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
