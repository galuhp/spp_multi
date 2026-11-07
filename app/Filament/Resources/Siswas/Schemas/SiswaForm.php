<?php

namespace App\Filament\Resources\Siswas\Schemas;

use App\Models\Kelas;
use App\Models\Spp;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

                TextInput::make('tahun_masuk')
                    ->label('Tahun Masuk / Angkatan')
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default(fn (): int => (int) now()->year)
                    ->helperText('Tahun angkatan masuk siswa (contoh: 2026).'),

                Select::make('kelas_id')
                    ->label('Rombongan Belajar / Kelas')
                    ->relationship('kelas', 'nama_kelas')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        $kelas = Kelas::with('tahunAjaran')->find($get('kelas_id'));
                        $tarif = Spp::untukKelas($kelas);

                        if ($tarif) {
                            $set('spp_id', $tarif->id);
                        }
                    })
                    ->required(),

                Select::make('spp_id')
                    ->label('Tarif SPP Bulanan')
                    ->options(function (Get $get): array {
                        $kelas = Kelas::with('tahunAjaran')->find($get('kelas_id'));

                        if (! $kelas) {
                            return [];
                        }

                        $namaTa = $kelas->tahunAjaran?->nama
                            ?? \App\Models\TahunAjaran::query()->where('aktif', true)->value('nama');

                        return Spp::query()
                            ->where('kategori', Spp::KATEGORI_SPP)
                            ->where('tipe', Spp::TIPE_BULANAN)
                            ->when($namaTa, fn ($query, $tahunAjaran) => $query->where('tahun_ajaran', $tahunAjaran))
                            ->where(function ($query) use ($kelas): void {
                                $query->where('tingkat', Spp::normalisasiTingkat($kelas->tingkat))
                                    ->orWhereNull('tingkat');
                            })
                            ->orderByRaw('tingkat is null')
                            ->get()
                            ->mapWithKeys(fn (Spp $spp): array => [
                                $spp->id => ($spp->tingkat ? 'Tingkat '.Spp::labelTingkat($spp->tingkat) : 'Semua Tingkat')
                                    .' — '.$spp->formattedNominal().' (TA '.$spp->tahun_ajaran.')',
                            ])
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Otomatis mengikuti tingkat kelas dan tahun ajaran berjalan.'),

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
