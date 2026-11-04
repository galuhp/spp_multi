<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Pembayarans\Pages\CreatePembayaran;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class EntriPembayaran extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static UnitEnum|string|null $navigationGroup = 'Transaksi SPP';

    protected static ?string $navigationLabel = 'Entri Pembayaran';

    protected static ?string $title = 'Entri Pembayaran';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.entri-pembayaran';

    public static function canAccess(): bool
    {
        return auth()->user()?->isStaff() ?? false;
    }

    /**
     * Highlight this menu on both the redirect page and the create page.
     *
     * @return array<int, string>
     */
    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [
            static::getRouteName(),
            CreatePembayaran::getRouteName(),
        ];
    }

    public function mount(): void
    {
        $this->redirect(CreatePembayaran::getUrl());
    }
}
