<?php

namespace App\Filament\Resources\PengeluaranSekolahs\Tables;

use App\Models\PengeluaranSekolah;
use App\Models\TahunAjaran;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PengeluaranSekolahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_transaksi')
                    ->label('No. Bukti')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->size('xs')
                    ->sortable(),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->size('sm')
                    ->color(fn (string $state): string => match ($state) {
                        PengeluaranSekolah::KATEGORI_OPERASIONAL => 'info',
                        PengeluaranSekolah::KATEGORI_GAJI => 'success',
                        PengeluaranSekolah::KATEGORI_ATK => 'warning',
                        PengeluaranSekolah::KATEGORI_SARPRAS => 'danger',
                        PengeluaranSekolah::KATEGORI_KEGIATAN => 'primary',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('keperluan')
                    ->label('Uraian / Keperluan')
                    ->description(fn (PengeluaranSekolah $record): string => $record->penerima ? 'Penerima: '.$record->penerima : '')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id')
                    ->alignment('right')
                    ->weight('bold')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total Pengeluaran')
                            ->money('IDR', locale: 'id'),
                    ),

                TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Tunai' ? 'gray' : 'info')
                    ->size('xs'),

                TextColumn::make('user.name')
                    ->label('Dicatat Oleh')
                    ->size('xs')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tahunAjaran.nama')
                    ->label('Tahun Ajaran')
                    ->size('xs')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options(PengeluaranSekolah::KATEGORI_LIST),

                SelectFilter::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->options(PengeluaranSekolah::METODE_LIST),

                SelectFilter::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama'),

                Filter::make('periode')
                    ->form([
                        DatePicker::make('dari')
                            ->label('Dari Tanggal')
                            ->native(false),
                        DatePicker::make('sampai')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari'] ?? null, fn (Builder $q, $date) => $q->whereDate('tanggal', '>=', $date))
                            ->when($data['sampai'] ?? null, fn (Builder $q, $date) => $q->whereDate('tanggal', '<=', $date));
                    }),
            ])
            ->actions([
                Action::make('cetak')
                    ->label('Cetak Bukti')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->url(fn (PengeluaranSekolah $record): string => route('pengeluaran.cetak', $record->id))
                    ->openUrlInNewTab(),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
