<?php

namespace App\Filament\Resources\PembayaranGabungans\RelationManagers;

use App\Models\PembayaranGabunganBaris;
use App\Models\Siswa;
use App\Support\PembayaranMassal\PembayaranMassalService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BarisRelationManager extends RelationManager
{
    protected static string $relationship = 'baris';

    protected static ?string $title = 'Baris Siswa';

    public function isReadOnly(): bool
    {
        return ! $this->getOwnerRecord()->isDraft();
    }

    /**
     * @return array<int, Component>
     */
    protected function formComponents(): array
    {
        return [
            Select::make('siswa_id')
                ->label('Siswa')
                ->options(fn (): array => Siswa::query()
                    ->aktif()
                    ->orderBy('nama')
                    ->get()
                    ->mapWithKeys(fn (Siswa $siswa): array => [
                        $siswa->id => "{$siswa->nama} (NISN: {$siswa->nisn} - {$siswa->kelas?->nama_kelas})",
                    ])
                    ->all())
                ->searchable()
                ->required(),

            TextInput::make('jumlah')
                ->label('Jumlah (Rp)')
                ->numeric()
                ->prefix('Rp')
                ->minValue(1)
                ->required(),

            TextInput::make('referensi')
                ->label('Referensi')
                ->maxLength(100)
                ->placeholder('No. referensi transfer / catatan'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->formComponents());
    }

    public function table(Table $table): Table
    {
        $service = app(PembayaranMassalService::class);

        return $table
            ->recordTitleAttribute('siswa.nama')
            ->columns([
                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siswa.nisn')
                    ->label('NISN')
                    ->fontFamily('mono')
                    ->placeholder('-'),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('referensi')
                    ->label('Referensi')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status_validasi')
                    ->label('Validasi')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        PembayaranGabunganBaris::STATUS_OK => 'OK',
                        PembayaranGabunganBaris::STATUS_ERROR => 'Error',
                        PembayaranGabunganBaris::STATUS_PERINGATAN => 'Peringatan',
                        default => (string) $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        PembayaranGabunganBaris::STATUS_OK => 'success',
                        PembayaranGabunganBaris::STATUS_ERROR => 'danger',
                        PembayaranGabunganBaris::STATUS_PERINGATAN => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('pesan')
                    ->label('Pesan')
                    ->limit(60)
                    ->placeholder('-'),
                TextColumn::make('alokasi')
                    ->label('Alokasi')
                    ->formatStateUsing(function ($state): string {
                        if (! is_array($state) || $state === []) {
                            return '-';
                        }

                        return collect($state)
                            ->map(fn (array $item): string => ($item['label'] ?? '-').' (Rp '.number_format((int) ($item['nominal'] ?? 0), 0, ',', '.').')')
                            ->implode(', ');
                    })
                    ->wrap(),
                TextColumn::make('pembayaran.kode_transaksi')
                    ->label('Kuitansi')
                    ->placeholder('-')
                    ->fontFamily('mono')
                    ->toggleable(),
            ])
            ->headerActions([
                Action::make('tambahSiswa')
                    ->label('Tambah Siswa')
                    ->icon('heroicon-o-plus')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft())
                    ->form($this->formComponents())
                    ->action(function (array $data) use ($service): void {
                        $service->tambahBaris(
                            $this->getOwnerRecord(),
                            (int) $data['siswa_id'],
                            (int) $data['jumlah'],
                            $data['referensi'] ?? null,
                        );
                    }),
            ])
            ->recordActions([
                Action::make('ubah')
                    ->label('Ubah')
                    ->icon('heroicon-o-pencil')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft())
                    ->form(fn (): array => collect($this->formComponents())
                        ->map(fn ($component) => $component->getName() === 'siswa_id' ? $component->disabled() : $component)
                        ->all())
                    ->fillForm(fn (PembayaranGabunganBaris $record): array => [
                        'siswa_id' => $record->siswa_id,
                        'jumlah' => $record->jumlah,
                        'referensi' => $record->referensi,
                    ])
                    ->action(function (PembayaranGabunganBaris $record, array $data) use ($service): void {
                        $service->perbaruiBaris(
                            $record,
                            (int) $data['jumlah'],
                            $data['referensi'] ?? null,
                        );
                    }),
                Action::make('alokasi')
                    ->label('Atur Alokasi')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft())
                    ->form(fn (PembayaranGabunganBaris $record): array => [
                        Repeater::make('alokasi')
                            ->label('Alokasi per tagihan')
                            ->schema([
                                TextInput::make('tagihan')
                                    ->label('Tagihan')
                                    ->disabled()
                                    ->dehydrated(true),
                                TextInput::make('nominal')
                                    ->label('Nominal (Rp)')
                                    ->numeric()
                                    ->minValue(0),
                            ])
                            ->default(fn (): array => collect($record->alokasi ?? [])
                                ->map(fn (array $item): array => [
                                    'tagihan' => $item['tagihan'] ?? '',
                                    'nominal' => (int) ($item['nominal'] ?? 0),
                                ])
                                ->values()
                                ->all())
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(2),
                    ])
                    ->action(function (PembayaranGabunganBaris $record, array $data) use ($service): void {
                        $alokasi = collect($data['alokasi'] ?? [])
                            ->filter(fn (array $item): bool => (int) ($item['nominal'] ?? 0) > 0)
                            ->map(fn (array $item): array => [
                                'tagihan' => $item['tagihan'],
                                'nominal' => (int) $item['nominal'],
                            ])
                            ->values()
                            ->all();

                        $service->perbaruiBaris($record, (int) $record->jumlah, $record->referensi, $alokasi);
                    }),
                Action::make('voidBaris')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PembayaranGabunganBaris $record): bool => $this->getOwnerRecord()->isPosted()
                        && ! $record->isVoid()
                        && (auth()->user()?->isAdmin() ?? false))
                    ->form([
                        Textarea::make('alasan')
                            ->label('Alasan void')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (PembayaranGabunganBaris $record, array $data) use ($service): void {
                        $service->voidBaris($record, (int) auth()->id(), $data['alasan']);
                    }),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft()),
            ]);
    }
}
