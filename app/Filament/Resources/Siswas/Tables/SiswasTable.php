<?php

namespace App\Filament\Resources\Siswas\Tables;

use App\Filament\Resources\Pembayarans\Pages\CreatePembayaran;
use App\Models\Siswa;
use App\Support\KenaikanKelas\ProsesKelasService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SiswasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nisn')
                    ->label('NISN')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nis')
                    ->label('NIS')
                    ->fontFamily('mono')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nama')
                    ->label('Nama Siswa')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('kelas.jurusan.kode')
                    ->label('Jurusan')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'lulus' ? 'success' : 'gray'),
                TextColumn::make('tanggungan_pemerintah_aktif_count')
                    ->label('Ditanggung Pemerintah')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? 'Ya' : 'Tidak')
                    ->color(fn (int $state): string => $state > 0 ? 'info' : 'gray'),
                IconColumn::make('is_archived')
                    ->label('Arsip')
                    ->boolean(),
                TextColumn::make('spp.nominal')
                    ->label('Tarif SPP')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format($state ?? 0, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('no_telp')
                    ->label('No. Telepon')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('arsip')
                    ->label('Status Data')
                    ->options([
                        'aktif' => 'Siswa Aktif',
                        'arsip' => 'Arsip',
                        'semua' => 'Semua',
                    ])
                    ->default('aktif')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'aktif') {
                            'arsip' => $query->where('is_archived', true),
                            'semua' => $query,
                            default => $query->where('is_archived', false),
                        };
                    }),
                SelectFilter::make('kelas_id')
                    ->label('Filter Kelas')
                    ->relationship('kelas', 'nama_kelas'),
                SelectFilter::make('ditanggung_pemerintah')
                    ->label('Ditanggung Pemerintah')
                    ->options([
                        'ya' => 'Ya',
                        'tidak' => 'Tidak',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'ya' => $query->whereHas('tanggunganPemerintah', fn (Builder $query): Builder => $query->whereNull('dicabut_at')),
                            'tidak' => $query->whereDoesntHave('tanggunganPemerintah', fn (Builder $query): Builder => $query->whereNull('dicabut_at')),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('keluarkanArsip')
                    ->label('Keluarkan dari Arsip')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Siswa $record): bool => $record->is_archived && (auth()->user()?->isAdmin() ?? false))
                    ->schema([
                        Textarea::make('alasan')
                            ->label('Alasan koreksi')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Siswa $record, array $data): void {
                        app(ProsesKelasService::class)->keluarkanDariArsip($record, $data['alasan']);

                        Notification::make()
                            ->title('Siswa dikeluarkan dari arsip')
                            ->success()
                            ->send();
                    }),
                Action::make('bayar')
                    ->label('Bayar')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->url(fn ($record): string => CreatePembayaran::getUrl(['siswa_id' => $record->id])),
                EditAction::make(),
            ]);
    }
}
