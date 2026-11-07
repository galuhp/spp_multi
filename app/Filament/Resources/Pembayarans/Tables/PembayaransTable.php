<?php

namespace App\Filament\Resources\Pembayarans\Tables;

use App\Models\Pembayaran;
use App\Support\Pembayaran\PembayaranService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
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
                    ->size('xs')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('tgl_bayar')
                    ->label('Tgl. Bayar')
                    ->date('d/m/Y')
                    ->size('xs')
                    ->sortable(),

                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->description(fn (Pembayaran $record): string => $record->siswa?->kelas?->nama_kelas ?? '-')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('siswa.kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('pos_pembayaran')
                    ->label('Pos Pembayaran')
                    ->getStateUsing(fn (Pembayaran $record): string => $record->namaPos())
                    ->badge()
                    ->size('sm')
                    ->wrap()
                    ->color(fn (Pembayaran $record): string => match ($record->spp?->kategori) {
                        'SPP' => 'info',
                        'Uang Gedung' => 'warning',
                        'Uang Seragam' => 'success',
                        default => 'primary',
                    }),

                TextColumn::make('jumlah_bayar')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format($state, 0, ',', '.'))
                    ->size('xs')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->badge()
                    ->size('sm')
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Petugas')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('void_at')
                    ->label('Status')
                    ->badge()
                    ->size('sm')
                    ->formatStateUsing(fn ($state): string => $state ? 'Void' : 'Valid')
                    ->color(fn ($state): string => $state ? 'danger' : 'success')
                    ->toggleable(),
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
                    ->tooltip('Cetak Kuitansi')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->outlined()
                    ->size('xs')
                    ->button()
                    ->url(fn (Pembayaran $record): string => route('pembayaran.cetak', $record->id))
                    ->openUrlInNewTab(),

                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->outlined()
                    ->size('xs')
                    ->button()
                    ->visible(fn (Pembayaran $record): bool => $record->void_at === null
                        && $record->gabungan_id === null
                        && (auth()->user()?->isAdmin() ?? false))
                    ->form([
                        Textarea::make('alasan')
                            ->label('Alasan void')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Pembayaran $record, array $data): void {
                        app(PembayaranService::class)->voidPembayaran(
                            $record,
                            (int) auth()->id(),
                            $data['alasan'],
                        );

                        Notification::make()
                            ->title('Transaksi di-void')
                            ->body('Riwayat dan nomor kuitansi tetap tersimpan.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
