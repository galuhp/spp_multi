<?php

namespace App\Http\Controllers;

use App\Models\PengeluaranSekolah;
use App\Models\ProfilSekolah;
use Illuminate\Contracts\View\View;

class PengeluaranController extends Controller
{
    /**
     * Printable voucher/receipt for school operational expenditure.
     */
    public function cetak(PengeluaranSekolah $pengeluaran): View
    {
        abort_unless(auth()->user()?->isStaff(), 403);

        $pengeluaran->loadMissing(['user', 'tahunAjaran']);

        return view('transaksi.pengeluaran-cetak', [
            'pengeluaran' => $pengeluaran,
            'sekolah' => ProfilSekolah::current(),
        ]);
    }
}
