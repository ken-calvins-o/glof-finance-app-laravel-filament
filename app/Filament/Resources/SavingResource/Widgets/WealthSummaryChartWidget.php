<?php

namespace App\Filament\Resources\SavingResource\Widgets;

use App\Filament\Resources\SavingResource\Pages\ListSavings;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class WealthSummaryChartWidget extends ChartWidget
{
    use InteractsWithPageTable;

    protected static ?string $heading = 'Wealth Summary';
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';

    public ?string $filter = 'month';

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Last Day',
            'week' => 'Last Week',
            '2weeks' => 'Last 2 Weeks',
            'month' => 'Last Month',
            '3months' => 'Last 3 Months',
            'year' => 'Last Year',
        ];
    }

    protected function getTablePage(): string
    {
        return ListSavings::class;
    }

    protected function getData(): array
    {
        $filter = $this->filter;

        $query = $this->getPageTableQuery();

        // Clear the order by clause to avoid interference with Trend
        $query->getQuery()->orders = [];

        // Define the start date based on the selected filter
        $startDate = $this->getStartDate($filter);

        // Fetch the trend data for different time periods (Total Savings)
        $amountData = $query->clone()  // Clone the query to avoid altering the original
        ->whereBetween('created_at', [$startDate, now()])
            ->selectRaw('date_format(created_at, "%d-%m-%Y") as date, sum(balance) as total_savings')
            ->groupByRaw('date_format(created_at, "%d-%m-%Y")')
            ->orderBy('date', 'asc')
            ->get();

        // Fetch the trend data for different time periods (Total Net Worth)
        $netWorthData = $query->clone()  // Clone the query to avoid altering the original
        ->whereBetween('created_at', [$startDate, now()])
            ->selectRaw('date_format(created_at, "%d-%m-%Y") as date, sum(net_worth) as total_net_worth')
            ->groupByRaw('date_format(created_at, "%d-%m-%Y")')
            ->orderBy('date', 'asc')
            ->get();

        // Combine the datasets for the chart
        return [
            'datasets' => [
                [
                    'label' => 'Total Savings (Balance)',
                    'data' => $amountData->map(fn($value) => $value->total_savings),
                    'borderColor' => 'rgb(75, 192, 192)', // Color for the savings line
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)', // Background color for the savings line
                ],
                [
                    'label' => 'Total Net Worth',
                    'data' => $netWorthData->map(fn($value) => $value->total_net_worth),
                    'borderColor' => 'rgb(153, 102, 255)', // Color for the net worth line
                    'backgroundColor' => 'rgba(153, 102, 255, 0.2)', // Background color for the net worth line
                ],
            ],

            // Merge the labels for both amount and net worth data
            'labels' => $amountData->map(fn($value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    // Helper method to determine the start date based on the selected filter
    protected function getStartDate(string $filter)
    {
        return match ($filter) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            '2weeks' => now()->subWeeks(2),
            '3months' => now()->subMonths(3),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
    }
}
