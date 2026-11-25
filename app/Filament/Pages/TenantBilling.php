<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use App\Models\TenantInvoice;
use App\Services\DokuService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Throwable;
use UnitEnum;

class TenantBilling extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan & Akses';

    protected static ?string $navigationLabel = 'Tagihan Pintarr';

    protected static ?string $title = 'Tagihan Pintarr';

    protected static ?string $slug = 'tenant-billing';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.tenant-billing';

    /**
     * @return Collection<int, TenantInvoice>
     */
    public function getInvoices(): Collection
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Tenant) {
            return new Collection();
        }

        return $tenant->invoices()
            ->orderByDesc('period')
            ->get();
    }

    public function payWithDoku(int $invoiceId, DokuService $dokuService): void
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $invoice = $tenant->invoices()->whereKey($invoiceId)->firstOrFail();

        if ($invoice->isPaid()) {
            Notification::make()
                ->title('Tagihan ini sudah lunas.')
                ->success()
                ->send();

            return;
        }

        try {
            $returnUrl = static::getUrl(tenant: $tenant);
            $paymentUrl = $dokuService->createCheckoutPayment($invoice, $returnUrl);

            $this->redirect($paymentUrl, navigate: false);
        } catch (Throwable $e) {
            Notification::make()
                ->title('Gagal memproses pembayaran DOKU')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
