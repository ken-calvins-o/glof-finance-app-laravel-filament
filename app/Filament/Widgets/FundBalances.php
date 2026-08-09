<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Support\Money;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * How much has been collected into each fund.
 *
 * The app tracks money per fund (Bereavement, Insurance, Administration, ...)
 * but nothing surfaced the split, so the only way to answer "how much is in
 * the bereavement kitty?" was to read the group statement matrix sideways.
 *
 * Drawn as bars rather than a pie: the question is "which fund is biggest and
 * by how much", and bars answer that far more precisely than angles do.
 */
class FundBalances extends Widget
{
    protected static string $view = 'filament.widgets.fund-balances';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    /**
     * @return Collection<int, array{name: string, total: float, share: float, formatted: string}>
     */
    public function getFunds(): Collection
    {
        $funds = Account::query()
            ->withSum('collections as total', 'amount')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Account $account) => [
                'name' => $account->name,
                'total' => (float) ($account->total ?? 0),
            ]);

        $max = (float) $funds->max('total') ?: 1.0;

        return $funds->map(fn (array $fund) => [
            ...$fund,
            'share' => round(($fund['total'] / $max) * 100),
            'formatted' => Money::kes($fund['total']),
        ]);
    }

    public function getGrandTotal(): string
    {
        return Money::kes($this->getFunds()->sum('total'));
    }
}
