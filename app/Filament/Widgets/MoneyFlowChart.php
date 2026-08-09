<?php

namespace App\Filament\Widgets;

use App\Services\GroupMetrics;
use Filament\Widgets\ChartWidget;

/**
 * Money in against money out, month by month.
 *
 * The chart this replaces plotted "total savings" and "total net worth" as
 * bars per *day*, summing the running-balance column across rows — which
 * double-counts history and produced a shape that meant nothing. Two series
 * that genuinely oppose each other (collections vs payments) is the comparison
 * a treasurer actually makes.
 */
class MoneyFlowChart extends ChartWidget
{
    protected static ?string $heading = 'Money in and out';

    protected static ?string $description = 'Collections received against payments made, over the last year.';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '280px';

    public ?string $filter = '12';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getFilters(): ?array
    {
        return [
            '6' => 'Last 6 months',
            '12' => 'Last 12 months',
            '24' => 'Last 2 years',
        ];
    }

    protected function getData(): array
    {
        $flow = app(GroupMetrics::class)->monthlyFlow((int) ($this->filter ?? 12));

        return [
            'datasets' => [
                [
                    'label' => 'Money in',
                    'data' => $flow->pluck('in')->all(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.75)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Money out',
                    'data' => $flow->pluck('out')->all(),
                    'backgroundColor' => 'rgba(244, 63, 94, 0.75)',
                    'borderColor' => 'rgb(244, 63, 94)',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $flow->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => ['usePointStyle' => true, 'boxWidth' => 8],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['drawBorder' => false],
                ],
                'x' => [
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }
}
