<?php

namespace App\Filament\Resources\Pembayarans\Schemas;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PembayaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Radio::make('cara_pilih')
                    ->label('Cara Memilih Siswa')
                    ->options([
                        'bertingkat' => 'Bertingkat (Jurusan → Kelas)',
                        'cari' => 'Cari Nama / NISN',
                    ])
                    ->default('bertingkat')
                    ->inline()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(fn (Set $set) => $set('siswa_id', null))
                    ->columnSpanFull(),

                Select::make('jurusan_id')
                    ->label('Kompetensi Keahlian (Jurusan)')
                    ->options(fn (): array => Jurusan::query()->orderBy('nama')->pluck('nama', 'id')->all())
                    ->searchable()
                    ->live()
                    ->dehydrated(false)
                    ->visible(fn (Get $get): bool => ($get('cara_pilih') ?? 'bertingkat') === 'bertingkat')
                    ->afterStateUpdated(fn (Set $set) => $set('kelas_id', null)),

                Select::make('kelas_id')
                    ->label('Kelas')
                    ->options(fn (Get $get): array => blank($get('jurusan_id'))
                        ? []
                        : Kelas::query()
                            ->where('jurusan_id', $get('jurusan_id'))
                            ->orderBy('tingkat')
                            ->orderBy('nama_kelas')
                            ->pluck('nama_kelas', 'id')
                            ->all())
                    ->searchable()
                    ->live()
                    ->dehydrated(false)
                    ->visible(fn (Get $get): bool => ($get('cara_pilih') ?? 'bertingkat') === 'bertingkat')
                    ->disabled(fn (Get $get): bool => blank($get('jurusan_id')))
                    ->afterStateUpdated(fn (Set $set) => $set('siswa_id', null)),

                Select::make('siswa_id')
                    ->label('Pilih Siswa')
                    ->options(function (Get $get): array {
                        if (($get('cara_pilih') ?? 'bertingkat') === 'cari') {
                            return Siswa::query()
                                ->with('kelas')
                                ->tanpaArsip()
                                ->orderBy('nama')
                                ->get()
                                ->mapWithKeys(fn (Siswa $s): array => [
                                    $s->id => "{$s->nama} (NISN: {$s->nisn} - {$s->kelas?->nama_kelas})",
                                ])
                                ->all();
                        }

                        if (blank($get('kelas_id'))) {
                            return [];
                        }

                        return Siswa::query()
                            ->where('kelas_id', $get('kelas_id'))
                            ->tanpaArsip()
                            ->orderBy('nama')
                            ->get()
                            ->mapWithKeys(fn (Siswa $s): array => [
                                $s->id => "{$s->nama} (NISN: {$s->nisn})",
                            ])
                            ->all();
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->disabled(fn (Get $get): bool => ($get('cara_pilih') ?? 'bertingkat') === 'bertingkat' && blank($get('kelas_id')))
                    ->helperText(fn (Get $get): string => ($get('cara_pilih') ?? 'bertingkat') === 'cari'
                        ? 'Ketik nama atau NISN untuk mencari siswa.'
                        : 'Pilih jurusan dan kelas terlebih dahulu.'),

                Radio::make('jenis_transaksi')
                    ->label('Jenis Pembayaran')
                    ->options([
                        'spp' => 'Iuran SPP Bulanan',
                        'non_spp' => 'Biaya Lainnya (Uang Gedung, Seragam, Praktik, dll.)',
                    ])
                    ->default('spp')
                    ->required()
                    ->live(),

                // Field SPP Bulanan
                CheckboxList::make('bulan_dibayar')
                    ->label('Pilih Bulan SPP yang Dibayar')
                    ->options(function (callable $get) {
                        $siswaId = $get('siswa_id');
                        if (! $siswaId) {
                            return [];
                        }

                        $siswa = Siswa::with('spp', 'pembayarans')->find($siswaId);
                        if (! $siswa) {
                            return [];
                        }

                        $activeYear = $siswa->spp?->tahun ?? (int) date('Y');
                        $paidMonths = $siswa->pembayarans
                            ->where('tahun_dibayar', $activeYear)
                            ->whereNotNull('bulan_dibayar')
                            ->pluck('bulan_dibayar')
                            ->all();

                        $unpaid = [];
                        foreach (Pembayaran::TAHUN_AJARAN_BULAN_LIST as $bulan) {
                            if (! in_array($bulan, $paidMonths, true)) {
                                $unpaid[$bulan] = "{$bulan} ({$siswa->spp?->formattedNominal()})";
                            }
                        }

                        return $unpaid;
                    })
                    ->columns(3)
                    ->visible(fn (callable $get) => $get('jenis_transaksi') === 'spp')
                    ->required(fn (callable $get) => $get('jenis_transaksi') === 'spp'),

                // Field Non-SPP
                Select::make('spp_id')
                    ->label('Pilih Pos Tagihan Non-SPP')
                    ->options(function (callable $get) {
                        $siswaId = $get('siswa_id');
                        if (! $siswaId) {
                            return [];
                        }

                        $siswa = Siswa::with('spp', 'pembayarans')->find($siswaId);
                        if (! $siswa) {
                            return [];
                        }

                        $activeYear = $siswa->spp?->tahun ?? (int) date('Y');
                        $activeTahunAjaran = $siswa->spp?->tahun_ajaran;

                        return Spp::where('kategori', '!=', 'SPP')
                            ->where(function ($q) use ($activeYear, $activeTahunAjaran) {
                                $q->where('tahun', $activeYear)
                                    ->orWhere('tahun_ajaran', $activeTahunAjaran);
                            })
                            ->get()
                            ->mapWithKeys(function ($tarif) use ($siswa) {
                                $dibayar = (int) $siswa->pembayarans->where('spp_id', $tarif->id)->sum('jumlah_bayar');
                                $sisa = max(0, $tarif->nominal - $dibayar);
                                $status = $sisa <= 0 ? ' [LUNAS]' : ' [Sisa: Rp '.number_format($sisa, 0, ',', '.').']';

                                return [$tarif->id => "{$tarif->kategori} - {$tarif->nama} (Total: {$tarif->formattedNominal()}){$status}"];
                            });
                    })
                    ->visible(fn (callable $get) => $get('jenis_transaksi') === 'non_spp')
                    ->required(fn (callable $get) => $get('jenis_transaksi') === 'non_spp')
                    ->live(),

                TextInput::make('jumlah_bayar')
                    ->label('Nominal yang Dibayar (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(1000)
                    ->step(5000)
                    ->visible(fn (callable $get) => $get('jenis_transaksi') === 'non_spp')
                    ->required(fn (callable $get) => $get('jenis_transaksi') === 'non_spp')
                    ->helperText('Dapat dibayar lunas atau dicicil sesuai kemampuan.'),

                DatePicker::make('tgl_bayar')
                    ->label('Tanggal Pembayaran')
                    ->default(now())
                    ->required(),

                Radio::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->options([
                        'Tunai' => 'Tunai',
                        'Transfer Bank' => 'Transfer Bank',
                        'QRIS' => 'QRIS',
                    ])
                    ->default('Tunai')
                    ->required()
                    ->inline(),

                TextInput::make('catatan')
                    ->label('Catatan Transaksi (Opsional)')
                    ->placeholder('Catatan atau keterangan pembayaran')
                    ->columnSpanFull(),
            ]);
    }
}
