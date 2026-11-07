<?php

namespace App\Filament\Resources\Siswas\Schemas;

use App\Models\Kelas;
use App\Models\Spp;
use App\Support\ImporSiswa\ReferensiImporSiswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas & Akademik Siswa')
                    ->columns(2)
                    ->components([
                        TextInput::make('nisn')
                            ->label('NISN (10 Digit)')
                            ->length(10)
                            ->numeric()
                            ->unique(ignoreRecord: true)
                            ->helperText('NISN akan digunakan sebagai Username default untuk login siswa (opsional jika belum ada).'),

                        TextInput::make('nik')
                            ->label('NIK (16 Digit)')
                            ->length(16)
                            ->numeric()
                            ->unique(ignoreRecord: true),

                        TextInput::make('nis')
                            ->label('NIS')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),

                        TextInput::make('nama')
                            ->label('Nama Lengkap Siswa')
                            ->required()
                            ->maxLength(100),

                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki (L)',
                                'P' => 'Perempuan (P)',
                            ])
                            ->required()
                            ->default('L'),

                        Select::make('agama')
                            ->label('Agama')
                            ->options(array_combine(ReferensiImporSiswa::AGAMA, ReferensiImporSiswa::AGAMA))
                            ->searchable(),

                        TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->maxLength(60),

                        DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('tanggal_masuk')
                            ->label('Tanggal Masuk Sekolah')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                if (filled($state) && blank($get('tahun_masuk'))) {
                                    $set('tahun_masuk', (int) substr($state, 0, 4));
                                }
                            }),

                        TextInput::make('tahun_masuk')
                            ->label('Tahun Masuk / Angkatan')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->default(fn (): int => (int) now()->year)
                            ->helperText('Otomatis terisi dari tahun Tanggal Masuk (contoh: 2026).'),

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
                            ->label('Nomor Telepon / WhatsApp Siswa')
                            ->tel()
                            ->placeholder('08xxxxxxxxxx atau 628xxxxxxxxxx')
                            ->maxLength(20)
                            ->regex('/^(?:\+?62|0)?8\d{7,12}$/')
                            ->validationMessages([
                                'regex' => 'Format nomor HP/WhatsApp siswa tidak valid (gunakan awalan 08 atau 628, 10–15 digit).',
                            ])
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                                ? (\App\Support\ImporSiswa\PembacaNilai::normalisasiHp($state) ?? trim($state))
                                : null)
                            ->helperText('Otomatis dinormalisasi ke format 628xxxxxxxxxx untuk pengiriman notifikasi WhatsApp.'),

                        Textarea::make('alamat')
                            ->label('Alamat Domisili Siswa')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Data Orang Tua / Wali')
                    ->relationship('wali')
                    ->columns(2)
                    ->components([
                        TextInput::make('nama_ayah')
                            ->label('Nama Ayah')
                            ->maxLength(100),
                        TextInput::make('nama_ibu')
                            ->label('Nama Ibu')
                            ->maxLength(100),
                        TextInput::make('nama_wali')
                            ->label('Nama Wali (Opsional)')
                            ->maxLength(100),
                        TextInput::make('hp_wali')
                            ->label('No. HP / WhatsApp Orang Tua / Wali')
                            ->tel()
                            ->placeholder('08xxxxxxxxxx atau 628xxxxxxxxxx')
                            ->maxLength(20)
                            ->regex('/^(?:\+?62|0)?8\d{7,12}$/')
                            ->validationMessages([
                                'regex' => 'Format nomor HP/WhatsApp wali murid tidak valid (gunakan awalan 08 atau 628, 10–15 digit).',
                            ])
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                                ? (\App\Support\ImporSiswa\PembacaNilai::normalisasiHp($state) ?? trim($state))
                                : null)
                            ->helperText('Digunakan untuk pengiriman notifikasi tagihan & kuitansi ke orang tua/wali.'),
                    ]),
            ]);
    }
}

