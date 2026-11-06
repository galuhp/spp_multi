<?php

namespace App\Filament\Resources\Siswas\RelationManagers;

use App\Models\Pembayaran;
use App\Models\SiswaTanggunganPemerintah;
use App\Models\Spp;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TanggunganPemerintahRelationManager extends RelationManager
{
    protected static string $relationship = 'tanggunganPemerintah';

    protected static ?string $title = 'Tanggungan Pemerintah';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cakupan')
                    ->label('Cakupan')
                    ->options(SiswaTanggunganPemerintah::cakupanOptions())
                    ->default(SiswaTanggunganPemerintah::CAKUPAN_SPP)
                    ->required()
                    ->live(),

                Select::make('spp_id')
                    ->label('Pos Biaya')
                    ->options(fn (): array => $this->posOptions())
                    ->searchable()
                    ->required(fn (Get $get): bool => $get('cakupan') === SiswaTanggunganPemerintah::CAKUPAN_POS)
                    ->visible(fn (Get $get): bool => $get('cakupan') === SiswaTanggunganPemerintah::CAKUPAN_POS),

                DatePicker::make('mulai')
                    ->label('Mulai Ditanggung')
                    ->default(now())
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cakupan')
            ->columns([
                TextColumn::make('cakupan')
                    ->label('Cakupan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SiswaTanggunganPemerintah::cakupanOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => $state === SiswaTanggunganPemerintah::CAKUPAN_SPP ? 'info' : 'warning'),

                TextColumn::make('spp.nama')
                    ->label('Pos Biaya')
                    ->placeholder('-')
                    ->description(fn (SiswaTanggunganPemerintah $record): ?string => $record->cakupan === SiswaTanggunganPemerintah::CAKUPAN_POS
                        ? $record->spp?->kategori
                        : null),

                TextColumn::make('mulai')
                    ->label('Mulai')
                    ->date('d/m/Y'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (SiswaTanggunganPemerintah $record): string => $record->dicabut_at === null ? 'Aktif' : 'Dicabut')
                    ->color(fn (string $state): string => $state === 'Aktif' ? 'success' : 'gray'),

                TextColumn::make('alasan')
                    ->label('Alasan')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->emptyStateHeading('Belum ada tanggungan pemerintah')
            ->emptyStateDescription('Tandai siswa ini sebagai ditanggung pemerintah untuk SPP atau pos biaya tertentu.')
            ->headerActions([
                CreateAction::make()
                    ->label('Tandai Ditanggung Pemerintah')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                Action::make('cabut')
                    ->label('Cabut')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SiswaTanggunganPemerintah $record): bool => $record->dicabut_at === null)
                    ->form([
                        Textarea::make('alasan')
                            ->label('Alasan pencabutan')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (SiswaTanggunganPemerintah $record, array $data): void {
                        $record->update([
                            'dicabut_at' => now(),
                            'alasan' => $data['alasan'],
                        ]);

                        Notification::make()
                            ->title('Tanggungan pemerintah dicabut')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * Non-SPP tariffs relevant to the selected student.
     *
     * @return array<int, string>
     */
    protected function posOptions(): array
    {
        $siswa = $this->getOwnerRecord();
        $tahunMulai = Pembayaran::tahunMulaiTarif($siswa->spp);
        $activeTahunAjaran = $siswa->spp?->tahun_ajaran;
        $tingkat = Spp::normalisasiTingkat($siswa->kelas?->tingkat);

        return Spp::query()
            ->nonSpp()
            ->where(function ($query) use ($tahunMulai, $activeTahunAjaran): void {
                $query->whereIn('tahun', [$tahunMulai, $tahunMulai + 1])
                    ->orWhere('tahun_ajaran', $activeTahunAjaran);
            })
            ->where(function ($query) use ($tingkat): void {
                $query->whereNull('tingkat');

                if ($tingkat) {
                    $query->orWhere('tingkat', $tingkat);
                }
            })
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn (Spp $tarif): array => [
                $tarif->id => "{$tarif->kategori} - ".($tarif->nama ?: 'Tanpa nama').' (Rp '.number_format((int) $tarif->nominal, 0, ',', '.').')',
            ])
            ->all();
    }
}
