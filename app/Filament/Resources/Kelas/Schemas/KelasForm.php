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
                Select::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama')
                    ->default(fn (): ?int => TahunAjaran::query()->where('aktif', true)->value('id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('tingkat')
                    ->label('Tingkat Kelas')
                    ->options([
                        'X' => 'Kelas X (Sepuluh)',
                        'XI' => 'Kelas XI (Sebelas)',
                        'XII' => 'Kelas XII (Dua Belas)',
                    ])
                    ->required(),
                TextInput::make('nama_kelas')
                    ->label('Nama Rombel Kelas')
                    ->placeholder('Contoh: XII RPL 1')
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                            ->where('tahun_ajaran_id', $get('tahun_ajaran_id')),
                    )
                    ->validationMessages([
                        'unique' => 'Nama kelas sudah terdaftar pada tahun ajaran ini.',
                    ])
                    ->required(),
                Select::make('jurusan_id')
                    ->label('Kompetensi Keahlian')
                    ->relationship('jurusan', 'nama')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}
