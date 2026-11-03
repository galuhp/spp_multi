<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $kelasId = $request->query('kelas_id');

        $siswas = Siswa::with(['kelas.jurusan', 'spp'])
            ->when($search, function ($query, $term) {
                return $query->where(function ($q) use ($term) {
                    $q->where('nama', 'like', "%{$term}%")
                        ->orWhere('nisn', 'like', "%{$term}%")
                        ->orWhere('nis', 'like', "%{$term}%");
                });
            })
            ->when($kelasId, function ($query, $id) {
                return $query->where('kelas_id', $id);
            })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        $kelasList = Kelas::orderBy('nama_kelas')->get();

        return view('siswa.index', [
            'siswas' => $siswas,
            'kelasList' => $kelasList,
            'search' => $search,
            'kelasId' => $kelasId,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $kelasList = Kelas::with('jurusan')->orderBy('nama_kelas')->get();
        $sppList = Spp::orderByDesc('tahun')->get();

        return view('siswa.create', compact('kelasList', 'sppList'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nisn' => ['required', 'string', 'max:15', 'unique:siswas,nisn'],
            'nis' => ['required', 'string', 'max:20', 'unique:siswas,nis'],
            'nama' => ['required', 'string', 'max:100'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'spp_id' => ['required', 'exists:spps,id'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'alamat' => ['nullable', 'string'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'create_user_account' => ['nullable', 'boolean'],
        ], [
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.unique' => 'NISN sudah terdaftar.',
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS sudah terdaftar.',
            'nama.required' => 'Nama siswa wajib diisi.',
            'kelas_id.required' => 'Kelas wajib dipilih.',
            'spp_id.required' => 'Tarif SPP wajib dipilih.',
        ]);

        $userId = null;
        if ($request->boolean('create_user_account')) {
            $user = User::create([
                'name' => $validated['nama'],
                'username' => $validated['nisn'],
                'password' => Hash::make($validated['nisn']), // default password is NISN
                'role' => UserRole::Siswa,
            ]);
            $userId = $user->id;
        }

        $validated['user_id'] = $userId;
        unset($validated['create_user_account']);

        Siswa::create($validated);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Siswa $siswa): View
    {
        $siswa->load(['kelas.jurusan', 'spp', 'user']);

        $riwayatPembayaran = $siswa->pembayarans()
            ->with(['user', 'spp'])
            ->latest('tgl_bayar')
            ->get();

        $activeYear = $siswa->spp?->tahun ?? (int) date('Y');
        $statusBulan = [];

        foreach (Pembayaran::TAHUN_AJARAN_BULAN_LIST as $bulan) {
            $pembayaran = $riwayatPembayaran->first(function ($p) use ($bulan, $activeYear) {
                return $p->bulan_dibayar === $bulan && $p->tahun_dibayar === $activeYear;
            });

            $statusBulan[] = [
                'bulan' => $bulan,
                'tahun' => $activeYear,
                'lunas' => $pembayaran !== null,
                'pembayaran' => $pembayaran,
            ];
        }

        return view('siswa.show', [
            'siswa' => $siswa,
            'riwayatPembayaran' => $riwayatPembayaran,
            'statusBulan' => $statusBulan,
            'activeYear' => $activeYear,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Siswa $siswa): View
    {
        $kelasList = Kelas::with('jurusan')->orderBy('nama_kelas')->get();
        $sppList = Spp::orderByDesc('tahun')->get();

        return view('siswa.edit', compact('siswa', 'kelasList', 'sppList'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $validated = $request->validate([
            'nisn' => ['required', 'string', 'max:15', 'unique:siswas,nisn,' . $siswa->id],
            'nis' => ['required', 'string', 'max:20', 'unique:siswas,nis,' . $siswa->id],
            'nama' => ['required', 'string', 'max:100'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'spp_id' => ['required', 'exists:spps,id'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'alamat' => ['nullable', 'string'],
            'no_telp' => ['nullable', 'string', 'max:20'],
        ], [
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.unique' => 'NISN sudah terdaftar.',
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS sudah terdaftar.',
            'nama.required' => 'Nama siswa wajib diisi.',
            'kelas_id.required' => 'Kelas wajib dipilih.',
            'spp_id.required' => 'Tarif SPP wajib dipilih.',
        ]);

        $siswa->update($validated);

        if ($siswa->user) {
            $siswa->user->update([
                'name' => $validated['nama'],
            ]);
        }

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Siswa $siswa): RedirectResponse
    {
        if ($siswa->pembayarans()->exists()) {
            return back()->with('error', 'Siswa tidak dapat dihapus karena sudah memiliki riwayat transaksi pembayaran SPP.');
        }

        if ($siswa->user) {
            $siswa->user->delete();
        }

        $siswa->delete();

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil dihapus.');
    }
}
