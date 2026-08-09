<?php

namespace App\Services;

use App\Enums\DebtStatusEnum;
use App\Models\Debt;
use App\Models\Payable;
use App\Models\Receivable;
use App\Models\Saving;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The group's numbers, defined once.
 *
 * Every screen that showed a total previously worked it out for itself: the
 * savings widget summed the visible table page, the group statement re-derived
 * "latest row per member" with its own `unique()` pass, and the loans table
 * fell back to a different balance than the debts table did. Same question,
 * three answers.
 *
 * All of that reasoning now lives here so the dashboard, the statement and the
 * member profile cannot disagree about how much money the group has.
 *
 * A note on `savings`: that table is an append-only ledger where each row
 * carries the running `balance` and `net_worth` after the transaction. So
 * "where does the group stand now" is the sum of the newest row per member —
 * never the sum of the whole column, which would count history repeatedly.
 */
class GroupMetrics
{
    /**
     * IDs of the newest savings row for each member.
     */
    protected function latestSavingIds(): \Illuminate\Database\Query\Builder
    {
        return Saving::query()
            ->toBase()
            ->selectRaw('MAX(id) as id')
            ->groupBy('user_id');
    }

    /**
     * Total cash members currently hold in their savings accounts.
     */
    public function totalSavings(): float
    {
        return (float) Saving::whereIn('id', $this->latestSavingIds())->sum('balance');
    }

    /**
     * Everything members have put in, less what has been taken out — the
     * group's worth as it stands.
     */
    public function totalNetWorth(): float
    {
        return (float) Saving::whereIn('id', $this->latestSavingIds())->sum('net_worth');
    }

    /**
     * Money the group is currently owed, whether from a loan or from arrears
     * on a fund.
     */
    public function totalOutstandingDebt(): float
    {
        return (float) Debt::whereIn('debt_status', DebtStatusEnum::outstandingValues())
            ->sum('outstanding_balance');
    }

    public function membersOwingCount(): int
    {
        return Debt::whereIn('debt_status', DebtStatusEnum::outstandingValues())
            ->where('outstanding_balance', '>', 0)
            ->distinct('user_id')
            ->count('user_id');
    }

    public function activeMemberCount(): int
    {
        return User::query()->active()->count();
    }

    public function memberCount(): int
    {
        return User::query()->count();
    }

    public function collectedBetween(CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) Receivable::whereBetween('created_at', [$from, $to])->sum('amount_contributed');
    }

    public function paidOutBetween(CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) Payable::whereBetween('created_at', [$from, $to])->sum('total_amount');
    }

    public function collectedThisMonth(): float
    {
        return $this->collectedBetween(now()->startOfMonth(), now()->endOfMonth());
    }

    public function collectedLastMonth(): float
    {
        return $this->collectedBetween(
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth(),
        );
    }

    /**
     * Money in and money out per month, oldest first.
     *
     * Grouped in PHP rather than in SQL on purpose: the previous chart used
     * MySQL's `date_format()`, which meant the dashboard could not run on any
     * other database. A savings group's transaction volume makes the cost of
     * grouping in PHP irrelevant.
     *
     * @return Collection<int, array{label: string, in: float, out: float}>
     */
    public function monthlyFlow(int $months = 12): Collection
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $in = Receivable::query()
            ->where('created_at', '>=', $start)
            ->get(['amount_contributed', 'created_at'])
            ->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m'))
            ->map(fn ($rows) => (float) $rows->sum('amount_contributed'));

        $out = Payable::query()
            ->where('created_at', '>=', $start)
            ->get(['total_amount', 'created_at'])
            ->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m'))
            ->map(fn ($rows) => (float) $rows->sum('total_amount'));

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($start, $in, $out) {
                $month = $start->copy()->addMonths($offset);
                $key = $month->format('Y-m');

                return [
                    'label' => $month->format('M y'),
                    'in' => (float) ($in[$key] ?? 0),
                    'out' => (float) ($out[$key] ?? 0),
                ];
            });
    }

    /**
     * A percentage change, guarding the "there was nothing before" case that
     * otherwise divides by zero.
     */
    public function percentageChange(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return $current == 0.0 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
