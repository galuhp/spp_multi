<?php

namespace App\Http\Controllers;

use App\Models\TenantInvoice;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TenantInvoicePrintController extends Controller
{
    public function __invoke(Request $request, TenantInvoice $invoice): View
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $invoice->loadMissing('tenant');

        abort_if($invoice->tenant === null, 404);
        abort_unless($user->canAccessTenant($invoice->tenant), 403);

        return view('billing.invoice-cetak', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
        ]);
    }
}
