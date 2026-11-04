<?php

namespace App\Filament\Resources\Pembayarans\Schemas;

use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PembayaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('siswa_id')
                    ->label('Pilih Siswa')
                    ->options(fn () => Siswa::with('kelas')->get()->mapWithKeys(function ($s) {
                        return [$s->id => "{$s->nama} (NISN: {$s->nisn} - {$s->kelas?->nama_kelas})"];
                    }))
                    ->searchable()
                    ->required()
                    ->live(),

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
