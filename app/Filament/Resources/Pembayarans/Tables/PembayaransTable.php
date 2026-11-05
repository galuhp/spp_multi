<?php

namespace App\Filament\Resources\Pembayarans\Tables;

use App\Models\Pembayaran;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PembayaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_transaksi')
                    ->label('No. Kuitansi')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('tgl_bayar')
                    ->label('Tgl. Bayar')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('siswa.nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('siswa.kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable(),

                TextColumn::make('pos_pembayaran')
                    ->label('Pos Pembayaran')
                    ->getStateUsing(fn (Pembayaran $record): string => $record->namaPos())
                    ->badge()
                    ->color(fn (Pembayaran $record): string => match ($record->spp?->kategori) {
                        'SPP' => 'info',
                        'Uang Gedung' => 'warning',
                        'Uang Seragam' => 'success',
                        default => 'primary',
                    }),

                TextColumn::make('jumlah_bayar')
                    ->label('Jumlah Bayar')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format($state, 0, ',', '.'))
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->badge(),

                TextColumn::make('user.name')
                    ->label('Petugas')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('tgl_bayar')
                    ->form([
                        DatePicker::make('tgl_dari')->label('Tanggal Dari'),
                        DatePicker::make('tgl_sampai')->label('Tanggal Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['tgl_dari'], fn ($q, $date) => $q->whereDate('tgl_bayar', '>=', $date))
                            ->when($data['tgl_sampai'], fn ($q, $date) => $q->whereDate('tgl_bayar', '<=', $date));
                    }),

                SelectFilter::make('kelas_id')
                    ->label('Filter Kelas')
                    ->relationship('siswa.kelas', 'nama_kelas'),
            ])
            ->recordActions([
                Action::make('cetak_kuitansi')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Pembayaran $record): string => route('pembayaran.cetak', $record->id))
                    ->openUrlInNewTab(),

                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                ]),
            ]);
    }
}
