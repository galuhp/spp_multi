<?php

namespace App\Filament\Pages;

use App\Models\RolePermission;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class EntriPembayaran extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static UnitEnum|string|null $navigationGroup = 'Transaksi SPP';

    protected static ?string $navigationLabel = 'Entri Pembayaran';

    protected static ?string $title = 'Entri Pembayaran';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.entri-pembayaran';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::TRANSAKSI_SPP);
    }

    public function mount(): void
    {
        $this->redirect(EntriPembayaranKasir::getUrl());
    }
}
