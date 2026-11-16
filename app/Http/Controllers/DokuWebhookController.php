<?php

namespace App\Http\Controllers;

use App\Models\TenantInvoice;
use App\Services\DokuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        if ($status === 'SUCCESS' && ! $invoice->isPaid()) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        return response()->json(['message' => 'OK']);
    }
}
