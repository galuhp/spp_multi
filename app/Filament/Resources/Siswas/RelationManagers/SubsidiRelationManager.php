<?php

namespace App\Filament\Resources\Siswas\RelationManagers;

use App\Models\SiswaSubsidi;
use App\Models\Spp;
use App\Models\TahunAjaran;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubsidiRelationManager extends RelationManager
{
    protected static string $relationship = 'subsidies';

    protected static ?string $title = 'Subsidi & Keringanan Biaya';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cakupan')
                    ->label('Cakupan Biaya')
                    ->options(SiswaSubsidi::cakupanOptions())
                    ->default(SiswaSubsidi::CAKUPAN_SPP)
                    ->required()
                    ->live(),

                Select::make('spp_id')
                    ->label('Pos Biaya Tertentu')
                    ->options(fn (): array => $this->posOptions())
                    ->searchable()
                    ->required(fn (Get $get): bool => $get('cakupan') === SiswaSubsidi::CAKUPAN_POS)
                    ->visible(fn (Get $get): bool => $get('cakupan') === SiswaSubsidi::CAKUPAN_POS),

                TextInput::make('persentase')
                    ->label('Persentase Subsidi (%)')
                    ->helperText('Contoh: 50 untuk subsidi 50% (siswa hanya bayar 50%).')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->suffix('%')
                    ->default(50)
                    ->required(),

                Select::make('jenis')
                    ->label('Kategori / Jalur')
                    ->options(SiswaSubsidi::jenisOptions())
                    ->default(SiswaSubsidi::JENIS_ANAK_GURU)
                    ->required(),

                Select::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->helperText('Kosongkan jika berlaku terus / tanpa batas tahun ajaran.')
                    ->options(fn (): array => TahunAjaran::query()->orderByDesc('nama')->pluck('nama', 'id')->all())
                    ->placeholder('Berlaku Semua Tahun Ajaran')
                    ->searchable(),

                TextInput::make('nomor_sk')
                    ->label('Nomor SK / Penetapan')
                    ->placeholder('Misal: SK/024/YYS/2026')
                    ->maxLength(100),

                DatePicker::make('mulai')
                    ->label('Mulai Berlaku')
                    ->default(now()),

                DatePicker::make('selesai')
                    ->label('Berakhir (Opsional)'),

                Toggle::make('is_aktif')
                    ->label('Status Aktif')
                    ->default(true),

                Textarea::make('keterangan')
                    ->label('Keterangan / Alasan Tambahan')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nomor_sk')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['spp', 'tahunAjaran']))
            ->columns([
                TextColumn::make('cakupan')
                    ->label('Cakupan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SiswaSubsidi::cakupanOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        SiswaSubsidi::CAKUPAN_SPP => 'info',
                        SiswaSubsidi::CAKUPAN_POS => 'warning',
                        default => 'purple',
                    }),

                TextColumn::make('spp.nama')
                    ->label('Pos Biaya')
                    ->placeholder('SPP Bulanan')
                    ->description(fn (SiswaSubsidi $record): ?string => $record->cakupan === SiswaSubsidi::CAKUPAN_POS
                        ? $record->spp?->kategori
                        : null),

                TextColumn::make('persentase')
                    ->label('Subsidi')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => "{$state}%")
                    ->color('warning'),

                TextColumn::make('jenis')
                    ->label('Kategori')
                    ->formatStateUsing(fn (string $state): string => SiswaSubsidi::jenisOptions()[$state] ?? $state),

                TextColumn::make('tahunAjaran.nama')
                    ->label('Tahun Ajaran')
                    ->placeholder('Semua TA'),

                IconColumn::make('is_aktif')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('nomor_sk')
                    ->label('No. SK')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->emptyStateHeading('Belum ada data subsidi')
            ->emptyStateDescription('Tambahkan keringanan/subsidi biaya dalam persen untuk siswa ini.')
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Subsidi')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function posOptions(): array
    {
        $siswa = $this->getOwnerRecord();
        $tingkat = Spp::normalisasiTingkat($siswa->kelas?->tingkat);

        return Spp::query()
            ->where('kategori', '!=', Spp::KATEGORI_SPP)
            ->when($tingkat, fn (Builder $query): Builder => $query->where('tingkat', $tingkat))
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn (Spp $spp): array => [
                $spp->id => "{$spp->nama} (Rp ".number_format($spp->nominal, 0, ',', '.').")",
            ])
            ->all();
    }
}
