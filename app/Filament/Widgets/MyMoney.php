<?php

namespace App\Filament\Widgets;

use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * What a member sees when they sign in.
 *
 * Previously a member landed on the same screen as the treasurer: Filament's
 * "you are logged in" card, then a sidebar full of the whole group's ledgers.
 * A member has exactly four questions about their own money, and this answers
 * all of them above the fold.
 */
class MyMoney extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return auth()->check() && ! auth()->user()->isAdmin();
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        $owing = $user->outstanding_debt;

        return [
            Stat::make('My savings', Money::kes($user->savings_balance))
                ->description('Available in your savings account')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),

            Stat::make('My net worth', Money::kes($user->net_worth))
                ->description('Everything you have put in, less what you have drawn')
                ->descriptionIcon('heroicon-m-scale')
                ->color('info'),

            Stat::make('Total contributed', Money::kes($user->total_contributed))
                ->description('Across all group funds')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('I owe', Money::kes($owing))
                ->description($owing > 0 ? 'Loans and arrears still to repay' : 'You are all paid up')
                ->descriptionIcon($owing > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($owing > 0 ? 'danger' : 'success'),
        ];
    }
}
