@php
    $tenant = \Filament\Facades\Filament::getTenant();
    $routeName = request()->route()?->getName();
    $graceInvoice = ($tenant instanceof \App\Models\Tenant
        && $routeName !== 'filament.admin.pages.tenant-billing'
        && ! $tenant->hasOverdueInvoices())
        ? $tenant->nextDueUnpaidInvoice()
        : null;
@endphp

@if ($graceInvoice)
    <div
        role="alert"
        style="background-color: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: 0.625rem; padding: 0.75rem 1rem; margin-bottom: 1rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;"
    >
        <div style="display: flex; align-items: center; gap: 0.625rem; font-size: 0.875rem;">
            <x-heroicon-o-clock style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" />
            <span>
                Tagihan platform periode <strong>{{ $graceInvoice->period }}</strong> sebesar
                <strong>Rp {{ number_format((int) $graceInvoice->total_amount, 0, ',', '.') }}</strong>
                menunggu pembayaran
                @if ($graceInvoice->due_date)
                    (jatuh tempo <strong>{{ $graceInvoice->due_date->format('d M Y') }}</strong>).
                @else
                    .
                @endif
            </span>
        </div>

        <a
            href="{{ \App\Filament\Pages\TenantBilling::getUrl(tenant: $tenant) }}"
            style="background-color: #92400e; color: #ffffff; border-radius: 0.375rem; padding: 0.375rem 0.75rem; font-size: 0.8125rem; font-weight: 600; text-decoration: none; white-space: nowrap;"
        >
            Lihat Tagihan
        </a>
    </div>
@endif
