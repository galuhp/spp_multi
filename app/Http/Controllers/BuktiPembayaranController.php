<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Support\TenantContext;
use Illuminate\Http\Response;

class BuktiPembayaranController extends Controller
{
    /**
     * Display the public payment verification receipt scanned via QR code.
     */
    public function show(string $kode, string $token): Response
    {
        $rows = Pembayaran::withoutGlobalScope('tenant')
            ->where('kode_transaksi', $kode)
            ->orderBy('id')
            ->get();

        $pembayaran = $rows->first(fn (Pembayaran $row): bool => $row->hasValidVerificationToken($token));

        if ($pembayaran === null) {
            return response()->view('pembayaran.bukti', [
                'valid' => false,
                'void' => false,
                'kode' => $kode,
            ], 404);
        }

        if ($pembayaran->tenant_id !== null) {
            TenantContext::setId((int) $pembayaran->tenant_id);
        }

        $rows->load(['siswa.kelas.jurusan', 'user', 'spp']);

        $activeRows = $rows->filter(fn (Pembayaran $row): bool => ! $row->isVoid())->values();

        if ($activeRows->isEmpty()) {
            $firstRow = $rows->first();

            return response()->view('pembayaran.bukti', [
                'valid' => false,
                'void' => true,
                'kode' => $kode,
                'alasan' => $pembayaran->void_alasan ?: $firstRow?->void_alasan,
            ], 410);
        }

        $utama = $pembayaran->isVoid() ? $activeRows->first() : $pembayaran;

        return response()->view('pembayaran.bukti', [
            'valid' => true,
            'void' => false,
            'pembayaran' => $utama,
            'relatedPayments' => $activeRows,
        ]);
    }
}

