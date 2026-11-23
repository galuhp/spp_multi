<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\PembayaranGabungan;
use App\Models\StudentPaymentOrder;
use App\Models\Tenant;
use App\Models\TenantPaymentGateway;
use App\Services\PaymentGateway\PaymentGatewayManager;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantPaymentWebhookController extends Controller
{
    public function handle(
        Request $request,
        string $provider,
        Tenant $tenant,
        PaymentGatewayManager $manager,
    ): JsonResponse {
        if (! $tenant->is_active) {
            return response()->json(['message' => 'Tenant inactive'], 404);
        }

        TenantContext::set($tenant);

        try {
            $config = TenantPaymentGateway::query()
                ->where('provider', $provider)
                ->where('is_active', true)
                ->first();

            if (! $config) {
                return response()->json(['message' => 'Payment gateway not active'], 404);
            }

            $driver = $manager->driver($provider);
            $result = $driver->verifyWebhook($request, $config);

            if (! $result->valid) {
                return response()->json(['message' => $result->errorMessage ?? 'Invalid signature'], 401);
            }

            if ($result->orderNumber === '') {
                return response()->json(['message' => 'Order number missing'], 422);
            }

            return DB::transaction(function () use ($tenant, $provider, $result): JsonResponse {
                $order = StudentPaymentOrder::query()
                    ->with('siswa')
                    ->where('order_number', $result->orderNumber)
                    ->lockForUpdate()
                    ->first();

                if (! $order) {
                    return response()->json(['message' => 'Order not found'], 404);
                }

                if ($result->amount !== (int) $order->total_amount) {
                    return response()->json(['message' => 'Amount mismatch'], 422);
                }

                if ($order->isPaid()) {
                    return response()->json(['status' => 'already_paid']);
                }

                if ($result->failedOrExpired) {
                    $order->update([
                        'status' => StudentPaymentOrder::STATUS_FAILED,
                        'raw_payload' => $result->rawPayload,
                    ]);

                    return response()->json(['status' => 'updated_failed']);
                }

                if ($result->paid) {
                    $gabungan = PembayaranGabungan::create([
                        'tenant_id' => $tenant->id,
                        'kode' => 'PG-'.now()->format('YmdHis').'-'.$order->id,
                        'tanggal' => now()->toDateString(),
                        'sumber' => PembayaranGabungan::SUMBER_TRANSFER_BANK,
                        'kelas_id' => $order->siswa?->kelas_id,
                        'total_diterima' => $order->total_amount,
                        'status' => PembayaranGabungan::STATUS_DIPOSTING,
                        'catatan' => 'Pembayaran online '.strtoupper($provider)." ({$order->order_number})",
                        'idempotency_token' => 'pg-'.$order->order_number,
                        'dibuat_oleh' => $order->user_id,
                        'diposting_oleh' => $order->user_id,
                        'diposting_at' => now(),
                    ]);

                    foreach ($order->items ?? [] as $item) {
                        Pembayaran::create([
                            'tenant_id' => $tenant->id,
                            'pembayaran_gabungan_id' => $gabungan->id,
                            'kode_transaksi' => Pembayaran::generateKodeTransaksi(),
                            'user_id' => $order->user_id,
                            'siswa_id' => $order->siswa_id,
                            'spp_id' => $item['spp_id'],
                            'tgl_bayar' => now()->toDateString(),
                            'bulan_dibayar' => $item['bulan_dibayar'] ?? null,
                            'tahun_dibayar' => $item['tahun_dibayar'] ?? (int) now()->year,
                            'jumlah_bayar' => (int) ($item['nominal'] ?? 0),
                            'metode_pembayaran' => strtoupper($provider),
                            'catatan' => 'Pembayaran online '.($item['label'] ?? 'SPP')." ({$order->order_number})",
                        ]);
                    }

                    $order->update([
                        'status' => StudentPaymentOrder::STATUS_PAID,
                        'paid_at' => now(),
                        'raw_payload' => $result->rawPayload,
                    ]);

                    return response()->json(['status' => 'paid']);
                }

                $order->update([
                    'raw_payload' => $result->rawPayload,
                ]);

                return response()->json(['status' => 'ok']);
            });
        } finally {
            TenantContext::clear();
        }
    }
}
