<?php

namespace App\Filament\Resources\Spps\Schemas;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SppForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kategori')
                    ->label('Kategori / Pos Tarif')
                    ->options([
                        'SPP' => 'SPP (Iuran Bulanan)',
                        'Uang Gedung' => 'Uang Gedung',
                        'Uang Seragam' => 'Uang Seragam',
                        'Biaya Praktik' => 'Biaya Praktik',
                        'Biaya Ujian' => 'Biaya Ujian',
                    ])
                    ->searchable()
                    ->createOptionUsing(fn (string $custom): string => trim($custom))
                    ->required()
                    ->default('SPP')
                    ->live()
                    ->afterStateUpdated(function (string $state, callable $set): void {
                        if ($state === 'SPP') {
                            $set('tipe', 'bulanan');
                        } else {
                            $set('tipe', 'sekali_bayar');
                        }
                    }),

                Radio::make('tipe')
                    ->label('Tipe Pembayaran')
                    ->options([
                        'bulanan' => 'Bulanan (12 Bulan / TA)',
                        'sekali_bayar' => 'Sekali Bayar / Bebas (Bisa dicicil)',
                    ])
                    ->required()
                    ->default('bulanan'),

                TextInput::make('nama')
                    ->label('Nama / Label Khusus (Opsional)')
                    ->placeholder('Contoh: Uang Gedung Angkatan 2026 atau Seragam Lengkap 4 Stel'),

                TextInput::make('tahun')
                    ->label('Tahun')
                    ->numeric()
                    ->default(date('Y'))
                    ->required(),

                TextInput::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->placeholder('Contoh: 2026/2027')
                    ->default(date('Y').'/'.(date('Y') + 1))
                    ->required(),

                TextInput::make('nominal')
                    ->label('Besaran Nominal Tarif')
                    ->prefix('Rp')
                    ->numeric()
                    ->minValue(1000)
                    ->step(5000)
                    ->required()
                    ->helperText('Untuk SPP: nominal per bulan. Untuk sekali bayar: total tagihan.'),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('Catatan atau keterangan pos tarif ini')
                    ->columnSpanFull(),
            ]);
    }
}
