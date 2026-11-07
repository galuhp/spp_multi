<?php

namespace App\Filament\Resources\PengeluaranSekolahs\Pages;

use App\Filament\Resources\PengeluaranSekolahs\PengeluaranSekolahResource;
use App\Models\PengeluaranSekolah;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListPengeluaranSekolahs extends ListRecords
{
    protected static string $resource = PengeluaranSekolahResource::class;

    protected static ?string $title = 'Transaksi Pengeluaran Operasional Sekolah';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak_rekap')
                ->label('Cetak Rekap Pengeluaran')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->modalHeading('Cetak Rekapitulasi Pengeluaran Sekolah')
                ->modalDescription('Pilih rentang tanggal dan kriteria pengeluaran yang ingin dicetak.')
                ->modalSubmitActionLabel('Buka Rekap Cetak')
                ->form([
                    DatePicker::make('tgl_awal')
                        ->label('Tanggal Awal')
                        ->default(now()->startOfMonth())
                        ->required(),
                    DatePicker::make('tgl_akhir')
                        ->label('Tanggal Akhir')
                        ->default(now())
                        ->required(),
                    Select::make('kategori')
                        ->label('Kategori Pengeluaran')
                        ->options(['' => 'Semua Kategori'] + PengeluaranSekolah::KATEGORI_LIST)
                        ->default(''),
                    Select::make('metode_pembayaran')
                        ->label('Metode Pembayaran')
                        ->options([
                            '' => 'Semua Metode',
                            'Tunai' => 'Tunai',
                            'Transfer Bank' => 'Transfer Bank',
                            'QRIS' => 'QRIS',
                        ])
                        ->default(''),
                ])
                ->action(function (array $data) {
                    $url = route('pengeluaran.rekap.cetak', array_filter([
                        'tgl_awal' => $data['tgl_awal'] ?? null,
                        'tgl_akhir' => $data['tgl_akhir'] ?? null,
                        'kategori' => $data['kategori'] ?? null,
                        'metode_pembayaran' => $data['metode_pembayaran'] ?? null,
                    ]));

                    $this->js("window.open('{$url}', '_blank')");
                }),

            CreateAction::make()
                ->label('Catat Pengeluaran Baru')
                ->icon('heroicon-o-plus'),
        ];
    }
}
