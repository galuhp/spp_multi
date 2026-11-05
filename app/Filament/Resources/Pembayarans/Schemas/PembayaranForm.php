<?php

namespace App\Filament\Resources\Pembayarans\Schemas;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PembayaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pilih Siswa')
                    ->description('Tentukan siswa yang akan dibayar melalui kelas atau pencarian nama/NISN.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        Radio::make('cara_pilih')
                            ->hiddenLabel()
                            ->options([
                                'cari' => 'Cari Nama / NISN',
                                'bertingkat' => 'Bertingkat (Kelas)',
                            ])
                            ->default('cari')
                            ->inline()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn (Set $set) => $set('siswa_id', null))
                            ->columnSpanFull(),

                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(fn (): array => Kelas::query()
                                ->with(['tahunAjaran', 'jurusan'])
                                ->orderByDesc('tahun_ajaran_id')
                                ->orderBy('tingkat')
                                ->orderBy('nama_kelas')
                                ->get()
                                ->mapWithKeys(fn (Kelas $kelas): array => [
                                    $kelas->id => trim(
                                        ($kelas->tahunAjaran?->nama ? $kelas->tahunAjaran->nama.' — ' : '')
                                        .$kelas->nama_kelas
                                        .($kelas->jurusan?->kode ? ' — '.$kelas->jurusan->kode : '')
                                    ),
                                ])
                                ->all())
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->visible(fn (Get $get): bool => ($get('cara_pilih') ?? 'bertingkat') === 'bertingkat')
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
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                if (blank($get('siswa_id'))) {
                                    return;
                                }

                                $jenis = (array) $get('jenis_transaksi');

                                if (! in_array('spp', $jenis, true)) {
                                    $jenis[] = 'spp';
                                    $set('jenis_transaksi', array_values(array_unique($jenis)));
                                }

                                $set('bulan_dibayar', []);
                                $set('non_spp_ids', []);
                            })
                            ->disabled(fn (Get $get): bool => ($get('cara_pilih') ?? 'bertingkat') === 'bertingkat' && blank($get('kelas_id')))
                            ->helperText(fn (Get $get): string => ($get('cara_pilih') ?? 'bertingkat') === 'cari'
                                ? 'Ketik nama atau NISN untuk mencari siswa.'
                                : 'Pilih kelas terlebih dahulu.'),
                    ]),

                Section::make('Detail Tagihan')
                    ->description('Pilih jenis pembayaran, lalu tentukan bulan SPP atau pos biaya non-SPP yang dibayar.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        CheckboxList::make('jenis_transaksi')
                            ->label('Jenis Pembayaran')
                            ->options([
                                'spp' => 'Iuran SPP Bulanan',
                                'non_spp' => 'Biaya Lainnya (Uang Gedung, Seragam, Praktik, dll.)',
                            ])
                            ->descriptions([
                                'spp' => 'Iuran bulanan untuk satu atau beberapa bulan.',
                                'non_spp' => 'Uang gedung, seragam, praktik, ujian, dan biaya lainnya.',
                            ])
                            ->extraAttributes(['class' => 'pembayaran-jenis-cards'])
                            ->default(['spp'])
                            ->columns(2)
                            ->required()
                            ->live()
                            ->columnSpanFull(),

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

                                $tahunMulai = Pembayaran::tahunMulaiTarif($siswa->spp);
                                $paidMonths = $siswa->pembayarans
                                    ->where('spp_id', $siswa->spp_id)
                                    ->whereNotNull('bulan_dibayar')
                                    ->pluck('bulan_dibayar')
                                    ->all();

                                $unpaid = [];
                                foreach (Pembayaran::bulanTahunAjaran($tahunMulai) as $periode) {
                                    if (! in_array($periode['bulan'], $paidMonths, true)) {
                                        $unpaid[$periode['bulan']] = "{$periode['bulan']} {$periode['tahun']} ({$siswa->spp?->formattedNominal()})";
                                    }
                                }

                                return $unpaid;
                            })
                            ->columns(3)
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => in_array('spp', (array) $get('jenis_transaksi'), true))
                            ->required(fn (Get $get): bool => in_array('spp', (array) $get('jenis_transaksi'), true)),

                        CheckboxList::make('non_spp_ids')
                            ->label('Pilih Biaya Lainnya')
                            ->options(function (Get $get): array {
                                $siswaId = $get('siswa_id');
                                if (! $siswaId) {
                                    return [];
                                }

                                $siswa = Siswa::with(['spp', 'kelas', 'pembayarans'])->find($siswaId);
                                if (! $siswa) {
                                    return [];
                                }

                                $tahunMulai = Pembayaran::tahunMulaiTarif($siswa->spp);
                                $activeTahunAjaran = $siswa->spp?->tahun_ajaran;
                                $tingkat = Spp::normalisasiTingkat($siswa->kelas?->tingkat);

                                return Spp::where('kategori', '!=', Spp::KATEGORI_SPP)
                                    ->where(function ($q) use ($tahunMulai, $activeTahunAjaran) {
                                        $q->whereIn('tahun', [$tahunMulai, $tahunMulai + 1])
                                            ->orWhere('tahun_ajaran', $activeTahunAjaran);
                                    })
                                    ->where(function ($q) use ($tingkat): void {
                                        $q->whereNull('tingkat');

                                        if ($tingkat) {
                                            $q->orWhere('tingkat', $tingkat);
                                        }
                                    })
                                    ->get()
                                    ->filter(function (Spp $tarif) use ($siswa): bool {
                                        $dibayar = (int) $siswa->pembayarans->where('spp_id', $tarif->id)->sum('jumlah_bayar');

                                        return ((int) $tarif->nominal - $dibayar) > 0;
                                    })
                                    ->mapWithKeys(function (Spp $tarif) use ($siswa): array {
                                        $dibayar = (int) $siswa->pembayarans->where('spp_id', $tarif->id)->sum('jumlah_bayar');
                                        $sisa = max(0, (int) $tarif->nominal - $dibayar);

                                        return [$tarif->id => "{$tarif->kategori} - {$tarif->nama} (Sisa: Rp ".number_format($sisa, 0, ',', '.').')'];
                                    })
                                    ->all();
                            })
                            ->columns(1)
                            ->visible(fn (Get $get): bool => in_array('non_spp', (array) $get('jenis_transaksi'), true))
                            ->required(fn (Get $get): bool => in_array('non_spp', (array) $get('jenis_transaksi'), true))
                            ->helperText('Centang biaya lain yang akan dibayar. Nominal yang disimpan adalah sisa tagihan penuh.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Pembayaran & Catatan')
                    ->description('Lengkapi tanggal, metode pembayaran, dan catatan transaksi.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
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
                    ]),
            ]);
    }
}
