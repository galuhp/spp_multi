<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\Tenant;
use App\Support\Laporan\LaporanSppService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    /**
     * Display report filter page and preview data.
     */
    public function index(Request $request): View
    {
        $this->pastikanAkses();

        $tglAwal = $request->query('tgl_awal', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $tglAkhir = $request->query('tgl_akhir', Carbon::now()->format('Y-m-d'));
        $kelasId = $request->query('kelas_id');
        $metode = $request->query('metode_pembayaran');
        $kategori = $request->query('kategori');

        $pembayarans = Pembayaran::with(['siswa.kelas.jurusan', 'siswa.subsidies', 'user', 'spp', 'gabungan'])->whereNull('void_at')
            ->when($tglAwal, function ($query, $tgl) {
                return $query->whereDate('tgl_bayar', '>=', $tgl);
            })
            ->when($tglAkhir, function ($query, $tgl) {
                return $query->whereDate('tgl_bayar', '<=', $tgl);
            })
            ->when($kelasId, function ($query, $id) {
                return $query->whereHas('siswa', function ($q) use ($id) {
                    $q->where('kelas_id', $id);
                });
            })
            ->when($metode, function ($query, $m) {
                return $query->where('metode_pembayaran', $m);
            })
            ->when($kategori, function ($query, $kat) {
                return $query->whereHas('spp', function ($q) use ($kat) {
                    $q->where('kategori', $kat);
                });
            })
            ->orderBy('tgl_bayar', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalPenerimaan = $pembayarans->sum('jumlah_bayar');
        $kelasList = Kelas::with('jurusan')->orderBy('nama_kelas')->get();
        $kategoriList = Spp::select('kategori')->distinct()->pluck('kategori')->all();

        return view('laporan.index', [
            'pembayarans' => $pembayarans,
            'totalPenerimaan' => $totalPenerimaan,
            'kelasList' => $kelasList,
            'tglAwal' => $tglAwal,
            'tglAkhir' => $tglAkhir,
            'kelasId' => $kelasId,
            'metode' => $metode,
            'kategori' => $kategori,
            'kategoriList' => $kategoriList,
        ]);
    }

    /**
     * Printable formal report view.
     */
    public function cetak(Request $request): View
    {
        $this->pastikanAkses();

        $tglAwal = $request->query('tgl_awal', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $tglAkhir = $request->query('tgl_akhir', Carbon::now()->format('Y-m-d'));
        $kelasId = $request->query('kelas_id');
        $metode = $request->query('metode_pembayaran');
        $kategori = $request->query('kategori');

        $pembayarans = Pembayaran::with(['siswa.kelas.jurusan', 'siswa.subsidies', 'user', 'spp', 'gabungan'])->whereNull('void_at')
            ->when($tglAwal, function ($query, $tgl) {
                return $query->whereDate('tgl_bayar', '>=', $tgl);
            })
            ->when($tglAkhir, function ($query, $tgl) {
                return $query->whereDate('tgl_bayar', '<=', $tgl);
            })
            ->when($kelasId, function ($query, $id) {
                return $query->whereHas('siswa', function ($q) use ($id) {
                    $q->where('kelas_id', $id);
                });
            })
            ->when($metode, function ($query, $m) {
                return $query->where('metode_pembayaran', $m);
            })
            ->when($kategori, function ($query, $kat) {
                return $query->whereHas('spp', function ($q) use ($kat) {
                    $q->where('kategori', $kat);
                });
            })
            ->orderBy('tgl_bayar', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $selectedKelas = $kelasId ? Kelas::find($kelasId) : null;
        $totalPenerimaan = $pembayarans->sum('jumlah_bayar');

        return view('laporan.cetak', [
            'pembayarans' => $pembayarans,
            'totalPenerimaan' => $totalPenerimaan,
            'tglAwal' => $tglAwal,
            'tglAkhir' => $tglAkhir,
            'selectedKelas' => $selectedKelas,
            'metode' => $metode,
            'kategori' => $kategori,
        ]);
    }

    /**
     * Printable outstanding SPP report.
     */
    public function cetakTunggakan(Request $request, LaporanSppService $service): View
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (! $user->isAdmin() && ! $user->isWaliKelas()) {
            if (! $user->isStaff() || (! RolePermission::allowed($user->role, RolePermission::LAPORAN) && ! RolePermission::allowed($user->role, RolePermission::LAPORAN_TUNGGAKAN))) {
                abort(403);
            }
        }

        $this->resolveTenantContext($request);

        $filters = [
            'tahun_ajaran_id' => $request->query('tahun_ajaran_id'),
            'kelas_id' => $request->query('kelas_id'),
            'tingkat' => $request->query('tingkat'),
            'status' => (string) $request->query('status', 'menunggak'),
            'urutkan' => (string) $request->query('urutkan', 'kelas'),
            'mode' => (string) $request->query('mode', ''),
        ];

        return view('laporan.tunggakan-cetak', [
            'laporan' => $service->tunggakan($filters),
            'filters' => $filters,
            'selectedKelas' => filled($filters['kelas_id']) ? Kelas::find($filters['kelas_id']) : null,
        ]);
    }

    /**
     * Printable received-vs-potential SPP report.
     */
    public function cetakPotensi(Request $request, LaporanSppService $service): View
    {
        $this->pastikanAkses();

        $filters = [
            'tahun_ajaran_id' => $request->query('tahun_ajaran_id'),
            'kelompok' => (string) $request->query('kelompok', LaporanSppService::KELOMPOK_KELAS),
            'tingkat' => $request->query('tingkat'),
            'jurusan_id' => $request->query('jurusan_id'),
            'kelas_id' => $request->query('kelas_id'),
        ];

        return view('laporan.potensi-cetak', [
            'laporan' => $service->rekapPotensi($filters, $filters['kelompok']),
            'filters' => $filters,
            'selectedKelas' => filled($filters['kelas_id']) ? Kelas::find($filters['kelas_id']) : null,
            'selectedJurusan' => filled($filters['jurusan_id']) ? Jurusan::find($filters['jurusan_id']) : null,
            'selectedTingkat' => filled($filters['tingkat']) ? Spp::labelTingkat((string) $filters['tingkat']) : null,
        ]);
    }

    /**
     * Printable student payment ledger.
     */
    public function cetakKartu(Request $request, LaporanSppService $service): View
    {
        $this->pastikanAkses(resolveTenant: false);

        $siswa = Siswa::withoutGlobalScope('tenant')->findOrFail($request->integer('siswa_id'));
        $this->resolveTenantContext($request, $siswa->tenant_id !== null ? (int) $siswa->tenant_id : null);

        $tahunAjaran = $service->resolveTahunAjaran($request->query('tahun_ajaran_id'));

        abort_if(! $tahunAjaran, 404, 'Tahun ajaran tidak ditemukan.');

        $view = $request->query('layout') === 'landscape'
            ? 'laporan.kartu-cetak-landscape'
            : 'laporan.kartu-cetak';

        return view($view, [
            'kartu' => $service->kartuPembayaran($siswa, $tahunAjaran),
            'rincian' => $service->rincianTagihan($siswa, $tahunAjaran),
        ]);
    }

    protected function pastikanAkses(bool $resolveTenant = true): void
    {
        $request = request();
        $user = $request->user();

        if (! $user?->isStaff() || ! RolePermission::allowed($user->role, RolePermission::LAPORAN)) {
            abort(403);
        }

        if ($resolveTenant) {
            $this->resolveTenantContext($request);
        }
    }

    protected function resolveTenantContext(Request $request, ?int $preferredTenantId = null): void
    {
        if (TenantContext::id() !== null) {
            return;
        }

        $user = $request->user();

        if (! $user) {
            return;
        }

        if ($preferredTenantId !== null) {
            $tenant = Tenant::query()->whereKey($preferredTenantId)->where('is_active', true)->first();

            if (! $tenant || ! $user->canAccessTenant($tenant)) {
                abort(403);
            }

            TenantContext::set($tenant);

            return;
        }

        if ($request->filled('tenant')) {
            $tenant = Tenant::query()
                ->where('slug', (string) $request->query('tenant'))
                ->where('is_active', true)
                ->first();

            if ($tenant && $user->canAccessTenant($tenant)) {
                TenantContext::set($tenant);

                return;
            }
        }

        $tenant = $user->isSuperAdmin()
            ? Tenant::query()->where('is_active', true)->orderBy('id')->first()
            : $user->tenants()->where('is_active', true)->orderBy('tenants.id')->first();

        if ($tenant) {
            TenantContext::set($tenant);
        }
    }
}
