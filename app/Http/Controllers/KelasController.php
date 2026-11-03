<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $kelasList = Kelas::with('jurusan')
            ->withCount('siswas')
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

        return view('kelas.create', compact('jurusans'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50', 'unique:kelas,nama_kelas'],
            'tingkat' => ['required', 'string', 'max:10'],
            'jurusan_id' => ['required', 'exists:jurusans,id'],
        ], [
            'nama_kelas.required' => 'Nama kelas wajib diisi.',
            'nama_kelas.unique' => 'Nama kelas sudah terdaftar.',
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

        return view('kelas.edit', [
            'kelas' => $kela,
            'jurusans' => $jurusans,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kelas $kela): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50', 'unique:kelas,nama_kelas,' . $kela->id],
            'tingkat' => ['required', 'string', 'max:10'],
            'jurusan_id' => ['required', 'exists:jurusans,id'],
        ], [
            'nama_kelas.required' => 'Nama kelas wajib diisi.',
            'nama_kelas.unique' => 'Nama kelas sudah terdaftar.',
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
