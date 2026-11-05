<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Spp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SppController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $kategori = $request->query('kategori');

        $spps = Spp::withCount('siswas')
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
            ->orderByDesc('tahun')
            ->orderBy('kategori')
            ->paginate(10)
            ->withQueryString();

        $kategoriList = Spp::query()
            ->select('kategori')
            ->distinct()
            ->pluck('kategori')
            ->all();

        $availableKategori = array_values(array_unique(array_merge(Spp::DEFAULT_KATEGORI_LIST, $kategoriList)));

        return view('spp.index', [
            'spps' => $spps,
            'kategori' => $kategori,
            'availableKategori' => $availableKategori,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $kategoriList = Spp::query()
            ->select('kategori')
            ->distinct()
            ->pluck('kategori')
            ->all();

        $availableKategori = array_values(array_unique(array_merge(Spp::DEFAULT_KATEGORI_LIST, $kategoriList)));

        return view('spp.create', [
            'availableKategori' => $availableKategori,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $kategori = $request->input('kategori') ?: Spp::KATEGORI_SPP;
        $tipe = $request->input('tipe') ?: ($kategori === Spp::KATEGORI_SPP ? Spp::TIPE_BULANAN : Spp::TIPE_SEKALI_BAYAR);
        $request->merge(['kategori' => $kategori, 'tipe' => $tipe]);

        $validated = $request->validate([
            'kategori' => ['required', 'string', 'max:50'],
            'tipe' => ['required', 'in:bulanan,sekali_bayar'],
            'nama' => ['nullable', 'string', 'max:100'],
            'tahun' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('spps')->where(function ($query) use ($request) {
                    $query->where('kategori', $request->input('kategori'))
                        ->where('tahun', $request->input('tahun'));

                    if ($request->input('kategori') === Spp::KATEGORI_SPP) {
                        $query->where('tingkat', $request->input('tingkat'));
                    } else {
                        $query->whereNull('tingkat');
                    }

                    return $query->when($request->filled('nama'), fn ($q) => $q->where('nama', $request->input('nama')));
                }),
            ],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'tingkat' => ['nullable', 'in:10,11,12', 'required_if:kategori,'.Spp::KATEGORI_SPP],
            'nominal' => ['required', 'integer', 'min:1000'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'kategori.required' => 'Kategori tarif wajib dipilih atau diisi.',
            'tipe.required' => 'Tipe pembayaran wajib dipilih.',
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.unique' => 'Tarif untuk kategori, tahun, dan tingkat ini sudah terdaftar.',
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi (contoh: 2026/2027).',
            'tingkat.required_if' => 'Tingkat wajib dipilih untuk tarif SPP.',
            'tingkat.in' => 'Tingkat harus X, XI, atau XII.',
            'nominal.required' => 'Besaran nominal tarif wajib diisi.',
            'nominal.min' => 'Nominal minimal Rp 1.000.',
        ]);

        if ($kategori !== Spp::KATEGORI_SPP) {
            $validated['tingkat'] = null;
        }

        $spp = Spp::create($validated);

        LogAktivitas::catat('spp.dibuat', "Tarif {$spp->kategori} ({$spp->namaLengkap()}) dibuat ({$spp->formattedNominal()})", $spp, $validated);

        return redirect()->route('spp.index')->with('success', "Tarif {$spp->kategori} berhasil ditambahkan.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Spp $spp): View
    {
        $kategoriList = Spp::query()
            ->select('kategori')
            ->distinct()
            ->pluck('kategori')
            ->all();

        $availableKategori = array_values(array_unique(array_merge(Spp::DEFAULT_KATEGORI_LIST, $kategoriList)));

        return view('spp.edit', [
            'spp' => $spp,
            'availableKategori' => $availableKategori,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Spp $spp): RedirectResponse
    {
        $kategori = $request->input('kategori') ?: ($spp->kategori ?? Spp::KATEGORI_SPP);
        $tipe = $request->input('tipe') ?: ($spp->tipe ?? Spp::TIPE_BULANAN);
        $request->merge(['kategori' => $kategori, 'tipe' => $tipe]);

        $validated = $request->validate([
            'kategori' => ['required', 'string', 'max:50'],
            'tipe' => ['required', 'in:bulanan,sekali_bayar'],
            'nama' => ['nullable', 'string', 'max:100'],
            'tahun' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('spps')->where(function ($query) use ($request, $spp) {
                    $query->where('kategori', $request->input('kategori'))
                        ->where('tahun', $request->input('tahun'))
                        ->where('id', '!=', $spp->id);

                    if ($request->input('kategori') === Spp::KATEGORI_SPP) {
                        $query->where('tingkat', $request->input('tingkat'));
                    } else {
                        $query->whereNull('tingkat');
                    }

                    return $query->when($request->filled('nama'), fn ($q) => $q->where('nama', $request->input('nama')));
                }),
            ],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'tingkat' => ['nullable', 'in:10,11,12', 'required_if:kategori,'.Spp::KATEGORI_SPP],
            'nominal' => ['required', 'integer', 'min:1000'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'kategori.required' => 'Kategori tarif wajib dipilih atau diisi.',
            'tipe.required' => 'Tipe pembayaran wajib dipilih.',
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.unique' => 'Tarif untuk kategori, tahun, dan tingkat ini sudah terdaftar.',
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi.',
            'tingkat.required_if' => 'Tingkat wajib dipilih untuk tarif SPP.',
            'tingkat.in' => 'Tingkat harus X, XI, atau XII.',
            'nominal.required' => 'Besaran nominal tarif wajib diisi.',
        ]);

        if ($kategori !== Spp::KATEGORI_SPP) {
            $validated['tingkat'] = null;
        }

        $sebelum = $spp->only(['kategori', 'tipe', 'nama', 'tahun', 'tahun_ajaran', 'tingkat', 'nominal', 'keterangan']);

        $spp->update($validated);

        LogAktivitas::catat('spp.diubah', "Tarif {$spp->kategori} ({$spp->namaLengkap()}) diubah", $spp, [
            'sebelum' => $sebelum,
            'sesudah' => $validated,
        ]);

        return redirect()->route('spp.index')->with('success', "Tarif {$spp->kategori} berhasil diperbarui.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Spp $spp): RedirectResponse
    {
        if ($spp->siswas()->exists()) {
            return back()->with('error', "Tarif {$spp->kategori} tidak dapat dihapus karena sudah dipakai oleh data siswa.");
        }

        if ($spp->pembayarans()->exists()) {
            return back()->with('error', "Tarif {$spp->kategori} tidak dapat dihapus karena sudah ada riwayat transaksi pembayaran.");
        }

        LogAktivitas::catat('spp.dihapus', "Tarif {$spp->kategori} ({$spp->namaLengkap()}) dihapus", $spp, $spp->only(['kategori', 'tipe', 'nama', 'tahun', 'tahun_ajaran', 'nominal', 'keterangan']));

        $spp->delete();

        return redirect()->route('spp.index')->with('success', "Tarif {$spp->kategori} berhasil dihapus.");
    }
}
