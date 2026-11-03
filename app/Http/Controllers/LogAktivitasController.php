<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogAktivitasController extends Controller
{
    /**
     * Display the activity log with filters.
     */
    public function index(Request $request): View
    {
        $userId = $request->query('user_id');
        $modul = $request->query('modul');
        $tglAwal = $request->query('tgl_awal');
        $tglAkhir = $request->query('tgl_akhir');

        $logs = LogAktivitas::with('user')
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
            ->when($modul, fn ($q, $m) => $q->where('aksi', 'like', $m . '.%'))
            ->when($tglAwal, fn ($q, $tgl) => $q->whereDate('created_at', '>=', $tgl))
            ->when($tglAkhir, fn ($q, $tgl) => $q->whereDate('created_at', '<=', $tgl))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('log.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(),
            'modulList' => [
                'pembayaran' => 'Transaksi Pembayaran',
                'spp' => 'Tarif SPP',
                'user' => 'Pengguna',
            ],
            'userId' => $userId,
            'modul' => $modul,
            'tglAwal' => $tglAwal,
            'tglAkhir' => $tglAkhir,
        ]);
    }
}
