<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\StudentPaymentOrder;
use App\Models\TenantPaymentGateway;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemHealthMonitor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?string $navigationLabel = 'Monitoring Sistem';

    protected static ?string $title = 'Monitoring Payment Gateway & Failed Jobs';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.super-admin.pages.system-health-monitor';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retryAllFailedJobs')
                ->label('Retry Semua Failed Jobs')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->visible(fn (): bool => $this->failedJobsCount > 0)
                ->requiresConfirmation()
                ->action(function (): void {
                    Artisan::call('queue:retry', ['id' => ['all']]);

                    Notification::make()
                        ->title('Semua failed jobs telah dikirim ulang ke antrean.')
                        ->success()
                        ->send();
                }),

            Action::make('flushFailedJobs')
                ->label('Hapus Semua Failed Jobs')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->visible(fn (): bool => $this->failedJobsCount > 0)
                ->requiresConfirmation()
                ->action(function (): void {
                    Artisan::call('queue:flush');

                    Notification::make()
                        ->title('Daftar failed jobs berhasil dibersihkan.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getActiveGatewaysCountProperty(): int
    {
        return TenantPaymentGateway::withoutGlobalScopes()
            ->where('is_active', true)
            ->count();
    }

    public function getProblemOrdersCountProperty(): int
    {
        return StudentPaymentOrder::withoutGlobalScopes()
            ->where(function ($query): void {
                $query->whereIn('status', [
                    StudentPaymentOrder::STATUS_FAILED,
                    StudentPaymentOrder::STATUS_EXPIRED,
                ])->orWhere(function ($pendingQuery): void {
                    $pendingQuery->where('status', StudentPaymentOrder::STATUS_PENDING)
                        ->where('created_at', '<', now()->subDay());
                });
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
    }

    public function getFailedJobsCountProperty(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return DB::table('failed_jobs')->count();
    }

    /**
     * @return Collection<int, TenantPaymentGateway>
     */
    public function getGatewaysProperty(): Collection
    {
        return TenantPaymentGateway::withoutGlobalScopes()
            ->with('tenant')
            ->latest('updated_at')
            ->get();
    }

    /**
     * @return Collection<int, StudentPaymentOrder>
     */
    public function getProblemOrdersProperty(): Collection
    {
        return StudentPaymentOrder::withoutGlobalScopes()
            ->with([
                'tenant',
                'siswa' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->where(function ($query): void {
                $query->whereIn('status', [
                    StudentPaymentOrder::STATUS_FAILED,
                    StudentPaymentOrder::STATUS_EXPIRED,
                ])->orWhere(function ($pendingQuery): void {
                    $pendingQuery->where('status', StudentPaymentOrder::STATUS_PENDING)
                        ->where('created_at', '<', now()->subDay());
                });
            })
            ->latest('id')
            ->limit(25)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function getFailedJobsProperty(): Collection
    {
        if (! Schema::hasTable('failed_jobs')) {
            return collect();
        }

        return DB::table('failed_jobs')
            ->orderByDesc('id')
            ->limit(25)
            ->get();
    }
}
