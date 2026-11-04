<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $kelasList = Kelas::with(['jurusan', 'tahunAjaran'])
            ->withCount('siswas')
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->paginate(10);

        return view('kelas.index', compact('kelasList'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $jurusans = Jurusan::orderBy('nama')->get();
        $tahunAjarans = TahunAjaran::orderByDesc('nama')->get();

        return view('kelas.create', compact('jurusans', 'tahunAjarans'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajarans,id'],
            'nama_kelas' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kelas', 'nama_kelas')->where('tahun_ajaran_id', $request->input('tahun_ajaran_id')),
            ],
            'tingkat' => ['required', 'string', 'max:10'],
            'jurusan_id' => ['required', 'exists:jurusans,id'],
        ], [
            'tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih.',
            'tahun_ajaran_id.exists' => 'Tahun ajaran tidak ditemukan.',
            'nama_kelas.required' => 'Nama kelas wajib diisi.',
            'nama_kelas.unique' => 'Nama kelas sudah terdaftar pada tahun ajaran ini.',
            'tingkat.required' => 'Tingkat kelas wajib dipilih.',
            'jurusan_id.required' => 'Jurusan wajib dipilih.',
        ]);

        Kelas::create($validated);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Kelas $kela): View
    {
        $jurusans = Jurusan::orderBy('nama')->get();
        $tahunAjarans = TahunAjaran::orderByDesc('nama')->get();

        return view('kelas.edit', [
            'kelas' => $kela,
            'jurusans' => $jurusans,
            'tahunAjarans' => $tahunAjarans,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kelas $kela): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajarans,id'],
            'nama_kelas' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kelas', 'nama_kelas')
                    ->where('tahun_ajaran_id', $request->input('tahun_ajaran_id'))
                    ->ignore($kela->id),
            ],
            'tingkat' => ['required', 'string', 'max:10'],
            'jurusan_id' => ['required', 'exists:jurusans,id'],
        ], [
            'tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih.',
            'tahun_ajaran_id.exists' => 'Tahun ajaran tidak ditemukan.',
            'nama_kelas.required' => 'Nama kelas wajib diisi.',
            'nama_kelas.unique' => 'Nama kelas sudah terdaftar pada tahun ajaran ini.',
            'tingkat.required' => 'Tingkat kelas wajib dipilih.',
            'jurusan_id.required' => 'Jurusan wajib dipilih.',
        ]);

        $kela->update($validated);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kelas $kela): RedirectResponse
    {
        if ($kela->siswas()->exists()) {
            return back()->with('error', 'Kelas tidak dapat dihapus karena masih memiliki data siswa.');
        }

        $kela->delete();

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
