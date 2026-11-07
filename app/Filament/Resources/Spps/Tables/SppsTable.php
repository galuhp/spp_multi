<?php

namespace App\Filament\Resources\Spps\Tables;

use App\Models\ProfilSekolah;
use App\Models\Spp;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tahun_ajaran', 'desc')
            ->columns([
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'SPP' => 'info',
                        'Uang Gedung' => 'warning',
                        'Uang Seragam' => 'success',
                        'Biaya Praktik' => 'danger',
                        default => 'primary',
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama')
                    ->label('Nama Pos')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'bulanan' ? 'Bulanan' : 'Sekali Bayar')
                    ->color(fn (string $state): string => $state === 'bulanan' ? 'sky' : 'emerald'),
                TextColumn::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? Spp::labelTingkat($state) : 'Semua Tingkat')
                    ->color(fn (?string $state): string => filled($state) ? 'primary' : 'success')
                    ->placeholder('Semua Tingkat'),
                TextColumn::make('nominal')
                    ->label('Nominal Tarif')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format($state, 0, ',', '.'))
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('siswas_count')
                    ->counts('siswas')
                    ->label('Siswa Terkait')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->options([
                        'SPP' => 'SPP',
                        'Uang Gedung' => 'Uang Gedung',
                        'Uang Seragam' => 'Uang Seragam',
                        'Biaya Praktik' => 'Biaya Praktik',
                        'Biaya Ujian' => 'Biaya Ujian',
                        'Lain-lain' => 'Lain-lain',
                    ]),
                SelectFilter::make('tipe')
                    ->options([
                        'bulanan' => 'Bulanan',
                        'sekali_bayar' => 'Sekali Bayar',
                    ]),
                SelectFilter::make('tingkat')
                    ->label('Tingkat')
                    ->options(fn (): array => ProfilSekolah::tingkatSppOptions()),
            ])
            ->recordActions([
                EditAction::make(),
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
