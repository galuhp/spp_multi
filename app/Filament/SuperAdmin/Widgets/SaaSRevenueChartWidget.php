<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\TenantInvoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SaaSRevenueChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Tren Tagihan & Pendapatan Fee (6 Bulan Terakhir)';

    protected function getData(): array
    {
        $months = collect(range(5, 0))
            ->map(fn (int $offset): Carbon => now()->startOfMonth()->subMonths($offset));

        $periods = $months->map(fn (Carbon $date): string => $date->format('Y-m'))->all();

        $invoices = TenantInvoice::query()
            ->whereIn('period', $periods)
            ->get(['period', 'status', 'total_amount']);

        $paidByPeriod = [];
        $billedByPeriod = [];

        foreach ($invoices as $invoice) {
            $period = (string) $invoice->period;
            $amount = (int) $invoice->total_amount;

            $billedByPeriod[$period] = ($billedByPeriod[$period] ?? 0) + $amount;

            if ($invoice->status === 'paid') {
                $paidByPeriod[$period] = ($paidByPeriod[$period] ?? 0) + $amount;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan Lunas (Rp)',
                    'data' => $months->map(fn (Carbon $date): int => $paidByPeriod[$date->format('Y-m')] ?? 0)->all(),
                    'borderColor' => '#18181b',
                    'backgroundColor' => 'rgba(24, 24, 27, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Total Tagihan Terbit (Rp)',
                    'data' => $months->map(fn (Carbon $date): int => $billedByPeriod[$date->format('Y-m')] ?? 0)->all(),
                    'borderColor' => '#71717a',
                    'backgroundColor' => 'rgba(113, 113, 122, 0.06)',
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months->map(fn (Carbon $date): string => $date->translatedFormat('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
