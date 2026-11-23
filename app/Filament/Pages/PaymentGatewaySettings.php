<?php

namespace App\Filament\Pages;

use App\Models\LogAktivitas;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\TenantPaymentGateway;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PaymentGatewaySettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan & Akses';

    protected static ?string $navigationLabel = 'Payment Gateway';

    protected static ?string $title = 'Pengaturan Payment Gateway Sekolah';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.payment-gateway-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() && RolePermission::allowed($user->role, RolePermission::PENGATURAN_AKSES);
    }

    public function mount(): void
    {
        $config = TenantPaymentGateway::query()
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();

        $provider = $config?->provider ?? TenantPaymentGateway::PROVIDER_DOKU;

        $this->fillFormForProvider($provider, $config);
    }

    protected function fillFormForProvider(string $provider, ?TenantPaymentGateway $existing = null): void
    {
        $config = $existing ?? TenantPaymentGateway::query()
            ->where('provider', $provider)
            ->first();

        $creds = $config?->credentials ?? [];

        $this->form->fill([
            'provider' => $provider,
            'is_active' => (bool) ($config?->is_active ?? false),
            'is_production' => (bool) ($config?->is_production ?? false),
            'credentials' => [
                'client_id' => $creds['client_id'] ?? '',
                'secret_key' => $creds['secret_key'] ?? '',
                'server_key' => $creds['server_key'] ?? '',
                'client_key' => $creds['client_key'] ?? '',
                'callback_token' => $creds['callback_token'] ?? '',
                'merchant_code' => $creds['merchant_code'] ?? '',
                'api_key' => $creds['api_key'] ?? '',
                'private_key' => $creds['private_key'] ?? '',
                'default_channel' => $creds['default_channel'] ?? '',
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Provider & Mode')
                    ->description('Setiap sekolah dapat menghubungkan akun Payment Gateway masing-masing agar pembayaran SPP siswa masuk langsung ke rekening sekolah.')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('provider')
                                ->label('Provider Payment Gateway')
                                ->options(TenantPaymentGateway::providerOptions())
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (?string $state): void {
                                    if ($state) {
                                        $this->fillFormForProvider($state);
                                    }
                                }),

                            Toggle::make('is_active')
                                ->label('Aktifkan Pembayaran Online')
                                ->helperText('Jika aktif, siswa bisa membayar tagihan langsung dari Portal Siswa.')
                                ->inline(false),

                            Toggle::make('is_production')
                                ->label('Mode Production (Live)')
                                ->helperText('Matikan untuk mode Sandbox / Uji Coba.')
                                ->inline(false),
                        ]),
                    ]),

                Section::make('Kredensial API Provider')
                    ->description('Kredensial disimpan secara terenkripsi di database.')
                    ->schema([
                        // DOKU
                        Grid::make(2)
                            ->visible(fn (Get $get): bool => $get('provider') === TenantPaymentGateway::PROVIDER_DOKU)
                            ->schema([
                                TextInput::make('credentials.client_id')
                                    ->label('DOKU Client ID')
                                    ->required(fn (Get $get): bool => (bool) $get('is_active') && $get('provider') === TenantPaymentGateway::PROVIDER_DOKU),
                                TextInput::make('credentials.secret_key')
                                    ->label('DOKU Secret Key')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (Get $get): bool => (bool) $get('is_active') && $get('provider') === TenantPaymentGateway::PROVIDER_DOKU),
                            ]),

                        // Midtrans
                        Grid::make(2)
                            ->visible(fn (Get $get): bool => $get('provider') === TenantPaymentGateway::PROVIDER_MIDTRANS)
                            ->schema([
                                TextInput::make('credentials.server_key')
                                    ->label('Midtrans Server Key')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (Get $get): bool => (bool) $get('is_active') && $get('provider') === TenantPaymentGateway::PROVIDER_MIDTRANS),
                                TextInput::make('credentials.client_key')
                                    ->label('Midtrans Client Key (Opsional)')
                                    ->password()
                                    ->revealable(),
                            ]),

                        // Xendit
                        Grid::make(2)
                            ->visible(fn (Get $get): bool => $get('provider') === TenantPaymentGateway::PROVIDER_XENDIT)
                            ->schema([
                                TextInput::make('credentials.secret_key')
                                    ->label('Xendit Secret API Key')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (Get $get): bool => (bool) $get('is_active') && $get('provider') === TenantPaymentGateway::PROVIDER_XENDIT),
                                TextInput::make('credentials.callback_token')
                                    ->label('Xendit Callback Verification Token')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (Get $get): bool => (bool) $get('is_active') && $get('provider') === TenantPaymentGateway::PROVIDER_XENDIT),
                            ]),

                        // Tripay
                        Grid::make(2)
                            ->visible(fn (Get $get): bool => $get('provider') === TenantPaymentGateway::PROVIDER_TRIPAY)
                            ->schema([
                                TextInput::make('credentials.merchant_code')
                                    ->label('Tripay Merchant Code')
                                    ->required(fn (Get $get): bool => (bool) $get('is_active') && $get('provider') === TenantPaymentGateway::PROVIDER_TRIPAY),
                                TextInput::make('credentials.api_key')
                                    ->label('Tripay API Key')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (Get $get): bool => (bool) $get('is_active') && $get('provider') === TenantPaymentGateway::PROVIDER_TRIPAY),
                                TextInput::make('credentials.private_key')
                                    ->label('Tripay Private Key')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (Get $get): bool => (bool) $get('is_active') && $get('provider') === TenantPaymentGateway::PROVIDER_TRIPAY),
                                TextInput::make('credentials.default_channel')
                                    ->label('Kode Channel Default')
                                    ->placeholder('QRIS')
                                    ->helperText('Contoh: QRIS, BRIVA, BCAVA, MANDIRIVA.'),
                            ]),

                        // Duitku
                        Grid::make(3)
                            ->visible(fn (Get $get): bool => $get('provider') === TenantPaymentGateway::PROVIDER_DUITKU)
                            ->schema([
                                TextInput::make('credentials.merchant_code')
                                    ->label('Duitku Merchant Code')
                                    ->required(fn (Get $get): bool => (bool) $get('is_active') && $get('provider') === TenantPaymentGateway::PROVIDER_DUITKU),
                                TextInput::make('credentials.api_key')
                                    ->label('Duitku API Key')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (Get $get): bool => (bool) $get('is_active') && $get('provider') === TenantPaymentGateway::PROVIDER_DUITKU),
                                TextInput::make('credentials.default_channel')
                                    ->label('Kode Metode Default')
                                    ->placeholder('SP')
                                    ->helperText('Contoh: SP (ShopeePay/QRIS), BC (BCA VA), M2 (Mandiri VA).'),
                            ]),
                    ]),
            ]);
    }

    public function getWebhookUrlProperty(): string
    {
        $tenant = Filament::getTenant();
        $provider = (string) ($this->data['provider'] ?? TenantPaymentGateway::PROVIDER_DOKU);

        if (! $tenant instanceof Tenant) {
            return '';
        }

        return route('webhooks.tenant-payment', [
            'provider' => $provider,
            'tenant' => $tenant->slug,
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $provider = (string) $state['provider'];
        $isActive = (bool) ($state['is_active'] ?? false);

        if ($isActive) {
            TenantPaymentGateway::query()
                ->where('provider', '!=', $provider)
                ->update(['is_active' => false]);
        }

        $credentials = collect($state['credentials'] ?? [])
            ->filter(fn ($v): bool => filled($v))
            ->all();

        $gateway = TenantPaymentGateway::updateOrCreate(
            ['provider' => $provider],
            [
                'is_active' => $isActive,
                'is_production' => (bool) ($state['is_production'] ?? false),
                'credentials' => $credentials,
            ]
        );

        LogAktivitas::catat(
            'pengaturan_payment_gateway',
            'Memperbarui pengaturan Payment Gateway ('.$gateway->providerLabel().')',
            $gateway,
        );

        Notification::make()
            ->title('Pengaturan Payment Gateway disimpan')
            ->success()
            ->send();
    }
}
