<?php

namespace App\Filament\Resources\SavingResource\Widgets;

use App\Filament\Resources\SavingResource\Pages\ListSavings;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WealthSummaryWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getTablePage(): string
    {
        return ListSavings::class;
    }
    protected function getStats(): array
    {
        // Get the current query from the page table
        $query = $this->getPageTableQuery();

        // Fetch total savings by summing the 'amount' column from the Savings table based on the current query
        $totalSavings = $query->sum('balance');

        // Fetch total net worth by summing the 'net_worth' column from the Savings table based on the current query
        $totalNetWorth = $query->sum('net_worth');

        return [
            Stat::make('Total Savings', 'KES ' . number_format($totalSavings, 2))
                ->icon('heroicon-s-currency-dollar') // User group icon
                ->extraAttributes(['class' => 'text-sm']),

            Stat::make('Total Net Worth', 'KES ' . number_format($totalNetWorth, 2))
                ->icon('heroicon-s-currency-dollar') // User group icon
                ->extraAttributes(['class' => 'text-sm']),
        ];
    }

}
