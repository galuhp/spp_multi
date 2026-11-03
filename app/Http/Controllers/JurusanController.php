<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JurusanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $jurusans = Jurusan::withCount('kelas')->latest('id')->paginate(10);

        return view('jurusan.index', compact('jurusans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('jurusan.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:jurusans,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
        ], [
            'kode.required' => 'Kode jurusan wajib diisi.',
            'kode.unique' => 'Kode jurusan sudah digunakan.',
            'nama.required' => 'Nama jurusan wajib diisi.',
        ]);

        Jurusan::create($validated);

        return redirect()->route('jurusan.index')->with('success', 'Jurusan berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Jurusan $jurusan): View
    {
        return view('jurusan.edit', compact('jurusan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Jurusan $jurusan): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:jurusans,kode,' . $jurusan->id],
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
        ], [
            'kode.required' => 'Kode jurusan wajib diisi.',
            'kode.unique' => 'Kode jurusan sudah digunakan.',
            'nama.required' => 'Nama jurusan wajib diisi.',
        ]);

        $jurusan->update($validated);

        return redirect()->route('jurusan.index')->with('success', 'Jurusan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Jurusan $jurusan): RedirectResponse
    {
        if ($jurusan->kelas()->exists()) {
            return back()->with('error', 'Jurusan tidak dapat dihapus karena masih memiliki relasi data kelas.');
        }

        $jurusan->delete();

        return redirect()->route('jurusan.index')->with('success', 'Jurusan berhasil dihapus.');
    }
}
