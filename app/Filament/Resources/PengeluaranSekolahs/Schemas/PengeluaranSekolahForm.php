<?php

namespace App\Filament\Resources\PengeluaranSekolahs\Schemas;

use App\Models\PengeluaranSekolah;
use App\Models\TahunAjaran;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PengeluaranSekolahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Transaksi Pengeluaran')
                    ->description('Catat rincian transaksi belanja operasional atau pengeluaran kas sekolah.')
                    ->columns(2)
                    ->components([
                        TextInput::make('nomor_transaksi')
                            ->label('Nomor Bukti / Transaksi')
                            ->default(fn (): string => PengeluaranSekolah::buatNomorTransaksi())
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Otomatis dibuat oleh sistem.')
                            ->maxLength(40),

                        DatePicker::make('tanggal')
                            ->label('Tanggal Pengeluaran')
                            ->default(now())
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Select::make('kategori')
                            ->label('Kategori Pengeluaran')
                            ->options(PengeluaranSekolah::KATEGORI_LIST)
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('nominal')
                            ->label('Nominal Pengeluaran')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(1)
                            ->helperText('Contoh: 250000'),

                        TextInput::make('keperluan')
                            ->label('Uraian / Keperluan')
                            ->placeholder('Contoh: Pembelian Kertas HVS & Spidol Whiteboard')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('penerima')
                            ->label('Penerima / Vendor / Toko')
                            ->placeholder('Contoh: Toko Buku Gramedia')
                            ->maxLength(100),

                        Select::make('metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->options(PengeluaranSekolah::METODE_LIST)
                            ->default(PengeluaranSekolah::METODE_TUNAI)
                            ->required(),

                        Select::make('tahun_ajaran_id')
                            ->label('Tahun Ajaran')
                            ->options(fn (): array => TahunAjaran::query()->orderByDesc('nama')->pluck('nama', 'id')->all())
                            ->default(fn (): ?int => TahunAjaran::query()->where('aktif', true)->value('id'))
                            ->searchable()
                            ->preload(),

                        FileUpload::make('bukti_transaksi')
                            ->label('Bukti Transaksi / Nota / Kwitansi')
                            ->directory('pengeluaran-bukti')
                            ->image()
                            ->maxSize(5120)
                            ->openable()
                            ->downloadable()
                            ->helperText('Unggah foto nota, kuitansi, atau faktur (maksimal 5 MB).'),

                        Textarea::make('catatan')
                            ->label('Catatan Tambahan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
