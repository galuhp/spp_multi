<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\PembayaranGabungans\PembayaranGabunganResource;
use App\Filament\Widgets\LatestPembayaranWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\ProfilSekolah;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $socialitePlugin = FilamentSocialitePlugin::make()
            ->providers([
                Provider::make('google')
                    ->label('Login with Google')
                    ->icon('fab-google')
                    ->color(Color::hex('#4285F4'))
                    ->outlined(false),
            ])
            ->registration(fn (string $provider, SocialiteUserContract $oauthUser, ?Authenticatable $user): bool => $user !== null);

        $allowedDomains = config('services.google.allowed_domains', []);

        if ($allowedDomains !== []) {
            $socialitePlugin->domainAllowList($allowedDomains);
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->plugin($socialitePlugin)
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): View => view('filament.auth.demo-login-buttons'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): View => view('filament.partials.pembayaran-form-styles'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): View => view('filament.partials.sidebar-styles'),
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): View => view('filament.partials.pembayaran-form-scripts'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): View => view('filament.partials.sidebar-user-menu'),
            )
            ->brandName(fn (): string => ProfilSekolah::current()->appName())
            ->brandLogo(fn (): View => view('filament.partials.brand'))
            ->brandLogoHeight('2.5rem')
            ->favicon(fn (): ?string => ProfilSekolah::current()->logoUrl())
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
            ])
            ->profile()
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $action->label('Profil Saya'),
                'logout' => fn (Action $action): Action => $action->label('Keluar'),
            ])
            ->navigationGroups([
                'Transaksi SPP',
                'Laporan',
                'Portal Siswa',
                'Kesiswaan',
                'Master Data',
                'Pengaturan & Akses',
            ])
            ->navigationItems([
                NavigationItem::make('Entry Pembayaran Gabungan')
                    ->key('buat-pembayaran-massal')
                    ->group('Transaksi SPP')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->sort(1)
                    ->url(fn (): string => PembayaranGabunganResource::getUrl('create'))
                    ->visible(fn (): bool => PembayaranGabunganResource::canCreate())
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.pembayaran-gabungans.create')),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                StatsOverviewWidget::class,
                LatestPembayaranWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
