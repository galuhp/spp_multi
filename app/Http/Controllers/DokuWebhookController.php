<?php

namespace App\Http\Controllers;

use App\Models\TenantInvoice;
use App\Services\DokuService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class DokuWebhookController extends Controller
{
    public function handle(Request $request, DokuService $dokuService): JsonResponse
    {
        $rawBody = $request->getContent();
        $clientId = (string) $request->header('Client-Id', '');
        $requestId = (string) $request->header('Request-Id', '');
        $timestamp = (string) $request->header('Request-Timestamp', '');
        $signature = (string) $request->header('Signature', '');
        $requestTarget = '/'.$request->path();

        if (! $dokuService->verifyWebhookSignature($clientId, $requestId, $timestamp, $requestTarget, $rawBody, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        try {
            $parsedTime = Carbon::parse($timestamp)->utc();
            if (abs(now()->utc()->diffInSeconds($parsedTime, false)) > 300) {
                return response()->json(['message' => 'Stale timestamp'], 401);
            }
        } catch (Throwable) {
            return response()->json(['message' => 'Invalid timestamp'], 401);
        }

        if ($requestId === '' || ! Cache::add("doku_webhook:{$requestId}", true, 600)) {
            return response()->json(['message' => 'Duplicate request'], 409);
        }

        $payload = $request->json()->all();
        $invoiceNumber = (string) data_get($payload, 'order.invoice_number', '');
        $status = strtoupper((string) data_get($payload, 'transaction.status', ''));

        if ($invoiceNumber === '') {
            return response()->json(['message' => 'Invoice number missing'], 422);
        }

        $invoice = TenantInvoice::query()
            ->where('doku_invoice_number', $invoiceNumber)
            ->first();

        if (! $invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $amount = (int) data_get($payload, 'order.amount', -1);
        $currency = strtoupper((string) data_get($payload, 'order.currency', ''));

        if ($amount !== (int) $invoice->total_amount || $currency !== 'IDR') {
            return response()->json(['message' => 'Amount or currency mismatch'], 422);
        }

        if ($status === 'SUCCESS' && ! $invoice->isPaid()) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        return response()->json(['message' => 'OK']);
    }
}
