<?php

namespace App\Filament\Resources\Pembayarans\Tables;

use App\Models\Pembayaran;
use App\Support\Pembayaran\PembayaranService;
use Carbon\Carbon;
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
                    ->formatStateUsing(fn ($state): string => $state ? 'Batal' : 'Valid')
                    ->color(fn ($state): string => $state ? 'danger' : 'success')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('tgl_bayar')
                    ->form([
                        DatePicker::make('tgl_dari')
                            ->label('Tanggal Dari')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('tgl_sampai')
                            ->label('Tanggal Sampai')
                            ->default(now()->endOfMonth()),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['tgl_dari'] ?? null) {
                            $indicators['tgl_dari'] = 'Dari: '.Carbon::parse($data['tgl_dari'])->format('d/m/Y');
                        }

                        if ($data['tgl_sampai'] ?? null) {
                            $indicators['tgl_sampai'] = 'Sampai: '.Carbon::parse($data['tgl_sampai'])->format('d/m/Y');
                        }

                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['tgl_dari'] ?? null, fn ($q, $date) => $q->whereDate('tgl_bayar', '>=', $date))
                            ->when($data['tgl_sampai'] ?? null, fn ($q, $date) => $q->whereDate('tgl_bayar', '<=', $date));
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
                    ->label('Batal')
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
                            ->label('Alasan pembatalan')
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
                            ->title('Transaksi dibatalkan')
                            ->body('Riwayat dan nomor kuitansi tetap tersimpan.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
