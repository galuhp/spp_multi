<?php

namespace App\Filament\Resources\Siswas\Schemas;

use App\Models\Spp;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nisn')
                    ->label('NISN (10 Digit)')
                    ->required()
                    ->length(10)
                    ->numeric()
                    ->unique(ignoreRecord: true)
                    ->helperText('NISN akan digunakan sebagai Username default untuk login siswa.'),

                TextInput::make('nis')
                    ->label('NIS (8 Digit)')
                    ->required()
                    ->length(8)
                    ->numeric()
                    ->unique(ignoreRecord: true),

                TextInput::make('nama')
                    ->label('Nama Lengkap Siswa')
                    ->required()
                    ->maxLength(100),

                Select::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ])
                    ->required()
                    ->default('L'),

                Select::make('kelas_id')
                    ->label('Rombongan Belajar / Kelas')
                    ->relationship('kelas', 'nama_kelas')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('spp_id')
                    ->label('Tarif SPP Bulanan')
                    ->options(fn () => Spp::where('kategori', 'SPP')->get()->mapWithKeys(function ($spp) {
                        return [$spp->id => "SPP TA {$spp->tahun_ajaran} ({$spp->formattedNominal()}/bln)"];
                    }))
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('no_telp')
                    ->label('Nomor Telepon / WhatsApp')
                    ->tel()
                    ->maxLength(15),

                Textarea::make('alamat')
                    ->label('Alamat Domisili Siswa')
                    ->columnSpanFull(),
            ]);
    }
}
