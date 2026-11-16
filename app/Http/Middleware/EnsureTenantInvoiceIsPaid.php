<?php

namespace App\Http\Middleware;

use App\Filament\Pages\TenantBilling;
use App\Models\Tenant;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantInvoiceIsPaid
{
    /**
     * Redirect to the tenant billing page if the tenant has unpaid invoices.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Tenant && $tenant->hasUnpaidInvoices()) {
            $routeName = $request->route()?->getName();

            if (
                $routeName !== 'filament.admin.pages.tenant-billing'
                && $routeName !== 'filament.admin.auth.logout'
            ) {
                return redirect()->to(TenantBilling::getUrl(tenant: $tenant));
            }
        }

        return $next($request);
    }
}
