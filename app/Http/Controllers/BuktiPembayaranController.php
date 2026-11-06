<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use Illuminate\Http\Response;

class BuktiPembayaranController extends Controller
{
    /**
     * Display the public payment verification receipt scanned via QR code.
     */
    public function show(string $kode, string $token): Response
    {
        $pembayaran = Pembayaran::with(['siswa.kelas.jurusan', 'user', 'spp'])
            ->where('kode_transaksi', $kode)
            ->first();

        if ($pembayaran === null || ! $pembayaran->hasValidVerificationToken($token)) {
            return response()->view('pembayaran.bukti', [
                'valid' => false,
                'void' => false,
                'kode' => $kode,
            ], 404);
        }

        if ($pembayaran->isVoid()) {
            return response()->view('pembayaran.bukti', [
                'valid' => false,
                'void' => true,
                'kode' => $kode,
                'alasan' => $pembayaran->void_alasan,
            ], 410);
        }

        return response()->view('pembayaran.bukti', [
            'valid' => true,
            'void' => false,
            'pembayaran' => $pembayaran,
            'relatedPayments' => $pembayaran->relatedBatchPayments(),
        ]);
    }
}
