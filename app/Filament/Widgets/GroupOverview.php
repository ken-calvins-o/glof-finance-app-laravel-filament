<?php

namespace App\Filament\Widgets;

use App\Services\GroupMetrics;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The four numbers a treasurer is asked for in every meeting.
 *
 * Chosen by asking "what question does this answer?" rather than "what columns
 * do we have": how much do we hold, what are we worth, who owes us, and are we
 * collecting as well as last month.
 */
class GroupOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getStats(): array
    {
        $metrics = app(GroupMetrics::class);

        $thisMonth = $metrics->collectedThisMonth();
        $lastMonth = $metrics->collectedLastMonth();
        $change = $metrics->percentageChange($thisMonth, $lastMonth);

        $owing = $metrics->totalOutstandingDebt();
        $membersOwing = $metrics->membersOwingCount();

        return [
            Stat::make('Cash in savings', Money::kes($metrics->totalSavings()))
                ->description('Held on behalf of members right now')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),

            Stat::make('Group net worth', Money::kes($metrics->totalNetWorth()))
                ->description('Contributions in, less money paid out')
                ->descriptionIcon('heroicon-m-scale')
                ->color('info'),

            Stat::make('Owed to the group', Money::kes($owing))
                ->description($membersOwing === 1
                    ? '1 member has an outstanding balance'
                    : "{$membersOwing} members have an outstanding balance")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($owing > 0 ? 'danger' : 'success'),

            Stat::make('Collected this month', Money::kes($thisMonth))
                ->description($this->describeChange($change, $lastMonth))
                ->descriptionIcon($change !== null && $change < 0
                    ? 'heroicon-m-arrow-trending-down'
                    : 'heroicon-m-arrow-trending-up')
                ->color(match (true) {
                    $change === null => 'gray',
                    $change < 0 => 'warning',
                    default => 'success',
                }),
        ];
    }

    protected function describeChange(?float $change, float $lastMonth): string
    {
        if ($change === null) {
            return 'Nothing was collected last month';
        }

        if ($change === 0.0) {
            return 'Same as last month';
        }

        $direction = $change > 0 ? 'up' : 'down';

        return sprintf('%s %s%% on last month (%s)', $direction, abs($change), Money::compact($lastMonth));
    }
}
