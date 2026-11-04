<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\PembayaranGabungan;
use App\Models\PembayaranGabunganBaris;
use App\Models\ProfilSekolah;
use App\Models\RolePermission;
use App\Support\PembayaranMassal\TemplateTransferBankExport;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PembayaranGabunganController extends Controller
{
    /**
     * Print all receipts belonging to a mass payment batch.
     */
    public function kuitansi(PembayaranGabungan $gabungan): View
    {
        $this->pastikanAkses();

        $groups = $gabungan->baris()
            ->whereNull('void_at')
            ->with('siswa.kelas.jurusan')
            ->orderBy('id')
            ->get()
            ->map(function ($baris): array {
                $payments = Pembayaran::query()
                    ->with(['siswa.kelas.jurusan', 'spp', 'user'])
                    ->where('gabungan_baris_id', $baris->id)
                    ->whereNull('void_at')
                    ->orderBy('id')
                    ->get();

                return ['baris' => $baris, 'payments' => $payments];
            })
            ->filter(fn (array $group): bool => $group['payments']->isNotEmpty())
            ->values();

        return view('pembayaran.gabungan-kuitansi', [
            'gabungan' => $gabungan,
            'groups' => $groups,
            'sekolah' => ProfilSekolah::current(),
        ]);
    }

    /**
     * Print the batch recap sheet.
     */
    public function rekap(PembayaranGabungan $gabungan): View
    {
        $this->pastikanAkses();

        $baris = $gabungan->baris()
            ->with(['siswa.kelas.jurusan'])
            ->whereNull('void_at')
            ->orderBy('id')
            ->get();

        return view('pembayaran.gabungan-rekap', [
            'gabungan' => $gabungan,
            'baris' => $baris,
            'sekolah' => ProfilSekolah::current(),
        ]);
    }

    /**
     * Download the transfer bank import template.
     */
    public function template(): BinaryFileResponse
    {
        $this->pastikanAkses();

        return Excel::download(new TemplateTransferBankExport, 'template-transfer-bank.xlsx');
    }

    /**
     * Download the import error report (CSV).
     */
    public function unduhError(PembayaranGabungan $gabungan): StreamedResponse
    {
        $this->pastikanAkses();

        $baris = $gabungan->baris()
            ->where('status_validasi', PembayaranGabunganBaris::STATUS_ERROR)
            ->orderBy('id')
            ->get();

        return Response::streamDownload(function () use ($baris): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['NIS/Referensi', 'Jumlah', 'Pesan']);

            foreach ($baris as $item) {
                fputcsv($handle, [
                    $item->referensi,
                    $item->jumlah,
                    $item->pesan,
                ]);
            }

            fclose($handle);
        }, 'laporan-error-'.$gabungan->kode.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function pastikanAkses(): void
    {
        $user = request()->user();

        if (! $user?->isStaff() || ! RolePermission::allowed($user->role, RolePermission::TRANSAKSI_SPP)) {
            abort(403);
        }
    }
}
