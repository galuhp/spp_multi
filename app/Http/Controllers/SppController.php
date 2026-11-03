<?php

namespace App\Http\Controllers;

use App\Models\Spp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SppController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $spps = Spp::withCount('siswas')
            ->orderByDesc('tahun')
            ->paginate(10);

        return view('spp.index', compact('spps'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('spp.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100', 'unique:spps,tahun'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'nominal' => ['required', 'integer', 'min:1000'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.unique' => 'Tarif untuk tahun ini sudah terdaftar.',
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi (contoh: 2026/2027).',
            'nominal.required' => 'Nominal SPP per bulan wajib diisi.',
            'nominal.min' => 'Nominal SPP minimal Rp 1.000.',
        ]);

        Spp::create($validated);

        return redirect()->route('spp.index')->with('success', 'Tarif SPP berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Spp $spp): View
    {
        return view('spp.edit', compact('spp'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Spp $spp): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100', 'unique:spps,tahun,' . $spp->id],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'nominal' => ['required', 'integer', 'min:1000'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.unique' => 'Tarif untuk tahun ini sudah terdaftar.',
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi.',
            'nominal.required' => 'Nominal SPP per bulan wajib diisi.',
        ]);

        $spp->update($validated);

        return redirect()->route('spp.index')->with('success', 'Tarif SPP berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Spp $spp): RedirectResponse
    {
        if ($spp->siswas()->exists()) {
            return back()->with('error', 'Tarif SPP tidak dapat dihapus karena sudah dipakai oleh data siswa.');
        }

        if ($spp->pembayarans()->exists()) {
            return back()->with('error', 'Tarif SPP tidak dapat dihapus karena sudah ada riwayat transaksi pembayaran.');
        }

        $spp->delete();

        return redirect()->route('spp.index')->with('success', 'Tarif SPP berhasil dihapus.');
    }
}
