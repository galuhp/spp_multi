<?php

namespace App\Filament\Resources\PembayaranGabungans\Schemas;

use App\Models\Kelas;
use App\Models\PembayaranGabungan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PembayaranGabunganForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode')
                    ->label('Kode Gabungan')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Otomatis saat disimpan'),

                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->disabled(fn (?PembayaranGabungan $record): bool => $record !== null && ! $record->isDraft())
                    ->required(),

                Select::make('sumber')
                    ->label('Sumber')
                    ->options(PembayaranGabungan::sumberOptions())
                    ->default(PembayaranGabungan::SUMBER_CATATAN_MANUAL)
                    ->disabled(fn (?PembayaranGabungan $record): bool => $record !== null && ! $record->isDraft())
                    ->required()
                    ->live(),

                Select::make('kelas_id')
                    ->label('Kelas (untuk setoran kelas)')
                    ->options(fn (): array => Kelas::query()
                        ->with(['tahunAjaran', 'jurusan'])
                        ->orderByDesc('tahun_ajaran_id')
                        ->orderBy('nama_kelas')
                        ->get()
                        ->mapWithKeys(fn (Kelas $kelas): array => [
                            $kelas->id => trim(($kelas->tahunAjaran?->nama ? $kelas->tahunAjaran->nama.' - ' : '').$kelas->nama_kelas),
                        ])
                        ->all())
                    ->searchable()
                    ->disabled(fn (?PembayaranGabungan $record): bool => $record !== null && ! $record->isDraft())
                    ->visible(fn (Get $get): bool => $get('sumber') === PembayaranGabungan::SUMBER_SETORAN_KELAS)
                    ->required(fn (Get $get): bool => $get('sumber') === PembayaranGabungan::SUMBER_SETORAN_KELAS),

                TextInput::make('total_diterima')
                    ->label('Total Diterima (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->minValue(0)
                    ->disabled(fn (?PembayaranGabungan $record): bool => $record !== null && ! $record->isDraft())
                    ->required()
                    ->helperText('Isi total uang yang benar-benar diterima. Posting hanya bisa jika total ini sama dengan total baris.'),

                Textarea::make('catatan')
                    ->label('Catatan')
                    ->rows(3)
                    ->disabled(fn (?PembayaranGabungan $record): bool => $record !== null && ! $record->isDraft())
                    ->columnSpanFull(),

                Placeholder::make('ringkasan_baris')
                    ->label('Ringkasan')
                    ->content(function (?PembayaranGabungan $record): string {
                        if (! $record) {
                            return 'Belum ada baris.';
                        }

                        $total = (int) $record->baris()->whereNull('void_at')->sum('jumlah');
                        $selisih = (int) $record->total_diterima - $total;

                        return 'Total baris: Rp '.number_format($total, 0, ',', '.')
                            .' · Total diterima: Rp '.number_format((int) $record->total_diterima, 0, ',', '.')
                            .' · Selisih: Rp '.number_format($selisih, 0, ',', '.');
                    })
                    ->columnSpanFull()
                    ->visible(fn (?PembayaranGabungan $record): bool => $record !== null),

                Placeholder::make('status_info')
                    ->label('Status')
                    ->content(fn (?PembayaranGabungan $record): string => PembayaranGabungan::statusOptions()[$record?->status ?? PembayaranGabungan::STATUS_DRAFT] ?? (string) $record?->status)
                    ->visible(fn (?PembayaranGabungan $record): bool => $record !== null),
            ]);
    }
}
