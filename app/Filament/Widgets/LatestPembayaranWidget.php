<?php

namespace App\Filament\Widgets;

use App\Models\Pembayaran;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestPembayaranWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->heading('Transaksi Pembayaran Terbaru')
            ->query(function (): Builder {
                $query = Pembayaran::query()->with(['siswa.kelas', 'user', 'spp'])->latest('id')->limit(8);

                if (auth()->user()?->isSiswa()) {
                    $siswa = auth()->user()->siswa;
                    if ($siswa) {
                        $query->where('siswa_id', $siswa->id);
                    }
                }

                return $query;
            })
            ->columns([
                TextColumn::make('kode_transaksi')
                    ->label('Kode')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->description(fn (Pembayaran $record): string => ($record->siswa?->kelas?->nama_kelas ?? '-').' (NISN: '.($record->siswa?->nisn ?? '-').')')
                    ->searchable(),
                TextColumn::make('spp.kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('bulan_dibayar')
                    ->label('Periode / Ket.')
                    ->formatStateUsing(function (Pembayaran $record): string {
                        if ($record->spp?->isBulanan()) {
                            return $record->bulan_dibayar.' '.$record->tahun_dibayar;
                        }

                        return $record->catatan ?: ($record->spp?->nama_tarif ?? 'Biaya Pendidikan');
                    }),
                TextColumn::make('jumlah_bayar')
                    ->label('Jumlah')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('tgl_bayar')
                    ->label('Tanggal')
                    ->date('d M Y'),
                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->default('Kasir'),
            ])
            ->recordActions([
                Action::make('cetak')
                    ->label('Kwitansi')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (Pembayaran $record): string => route('pembayaran.cetak', $record->id))
                    ->openUrlInNewTab(),
            ]);
    }
}
