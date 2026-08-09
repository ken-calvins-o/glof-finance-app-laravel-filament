<?php

namespace App\Services;

use App\Enums\DebtStatusEnum;
use App\Enums\PaymentMode;
use App\Models\AccountCollection;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Month;
use App\Models\MonthlyReceivable;
use App\Models\Receivable;
use App\Models\ReceivableYear;
use App\Models\Saving;
use App\Models\Year;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Applying a repayment to a debt.
 *
 * This is a straight extraction of what the debt edit page already did, moved
 * out of the page class so that a repayment can be recorded from anywhere —
 * the dashboard's "who owes us" list, the member's profile, or the debts table
 * — without the treasurer having to navigate into a full edit form to type one
 * number.
 *
 * Behaviour is unchanged. The one difference is that the failure cases now
 * raise exceptions with usable messages instead of sending a notification and
 * then throwing a second, separate error.
 */
class DebtRepaymentService
{
    /**
     * @throws RuntimeException when the repayment cannot be applied.
     */
    public function apply(Debt $debt, float $amount, bool $fromSavings = false): Debt
    {
        return DB::transaction(function () use ($debt, $amount, $fromSavings) {
            if ($amount <= 0) {
                throw new RuntimeException('Enter a repayment amount greater than zero.');
            }

            if ($amount > (float) $debt->outstanding_balance) {
                throw new RuntimeException(sprintf(
                    'That is more than is owed. The outstanding balance is KES %s.',
                    number_format((float) $debt->outstanding_balance, 2),
                ));
            }

            $userId = $debt->user_id;
            $accountId = $debt->account_id;

            // A debt with no account is a credited loan; anything else must be
            // tied to a fund so the repayment can be credited back to it.
            $isCreditedLoan = is_null($accountId) && Loan::where('user_id', $userId)->exists();

            if (is_null($accountId) && ! $isCreditedLoan) {
                throw new RuntimeException('This debt is not linked to a fund, so a repayment cannot be recorded against it.');
            }

            // Reduce what is owed, and settle the debt when nothing is left.
            $debt->outstanding_balance = max(0, (float) $debt->outstanding_balance - $amount);
            $debt->debt_status = $debt->outstanding_balance > 0
                ? DebtStatusEnum::Partially_Paid
                : DebtStatusEnum::Cleared;
            $debt->save();

            // Keep the loan record in step with its debt.
            $loan = Loan::where('user_id', $userId)->first();

            if ($loan) {
                $loan->balance = max(0, (float) $loan->balance - $amount);
                $loan->debt_status = $loan->balance > 0
                    ? DebtStatusEnum::Partially_Paid
                    : DebtStatusEnum::Cleared;
                $loan->save();
            }

            /*
             * A repayment is money coming in, so record it as a collection.
             *
             * It was previously written only to the debt, the fund's running
             * total and the savings ledger — never as a Receivable. But
             * Receivable is what the Collections screen, a member's recent
             * activity and the dashboard's "collected this month" all read, so a
             * member who paid 4,000, was charged 5,000 and then settled the
             * 1,000 shortfall still showed 4,000 collected, and the repayment
             * appeared nowhere.
             *
             * Two deliberate limits:
             *  - Paying from savings is excluded. That moves money the group
             *    already holds for the member; nothing new comes in, so counting
             *    it as a collection would inflate the totals.
             *  - Only fund debts are recorded. A loan repayment has no fund to
             *    file against, and receivables.account_id cannot be null.
             */
            if (! is_null($accountId) && ! $fromSavings) {
                $this->recordAsCollection($debt, $amount);
            }

            // Credit the repayment back to the fund it was owed against.
            if (! is_null($accountId)) {
                AccountCollection::firstOrCreate(
                    ['user_id' => $userId, 'account_id' => $accountId],
                    ['amount' => 0],
                )->increment('amount', $amount);
            }

            // Post the movement to the member's savings ledger.
            $latest = Saving::where('user_id', $userId)->latest('id')->first();
            $currentBalance = (float) ($latest->balance ?? 0);
            $currentNetWorth = (float) ($latest->net_worth ?? 0);

            $newBalance = $currentBalance;

            if ($fromSavings) {
                $newBalance -= $amount;

                if ($newBalance < 0) {
                    throw new RuntimeException(sprintf(
                        'This member only has KES %s in savings, which is not enough to cover KES %s.',
                        number_format($currentBalance, 2),
                        number_format($amount, 2),
                    ));
                }
            }

            Saving::create([
                'user_id' => $userId,
                'credit_amount' => $fromSavings ? 0 : $amount,
                'debit_amount' => $fromSavings ? $amount : 0,
                'balance' => $newBalance,
                'net_worth' => $currentNetWorth + $amount,
            ]);

            return $debt->refresh();
        });
    }

    /**
     * Whether the receivables table has the `source` column yet.
     *
     * Cached for the life of the process: the answer cannot change under a
     * running request, and this sits on the repayment path.
     */
    protected static ?bool $receivablesCanRecordSource = null;

    protected static function receivablesCanRecordSource(): bool
    {
        return static::$receivablesCanRecordSource ??= Schema::hasColumn('receivables', 'source');
    }

    /**
     * Write the repayment into the collections ledger.
     *
     * Note this does NOT touch the fund's running total or the savings ledger —
     * apply() does both itself, immediately after calling this. Creating the row
     * here only makes the money visible on the screens that read collections.
     */
    protected function recordAsCollection(Debt $debt, float $amount): void
    {
        $attributes = [
            'user_id' => $debt->user_id,
            'account_id' => $debt->account_id,
            'amount_contributed' => $amount,
            'from_savings' => false,
            'payment_method' => PaymentMode::Cash->value,
        ];

        /*
         * `source` only labels the row; it moves no money. So if the migration
         * that adds it has not run yet — code deployed ahead of migrations is an
         * ordinary thing to happen — the repayment still goes through without
         * it, rather than the whole operation failing on a column that exists
         * to caption a table.
         *
         * Until the migration runs, such a row is indistinguishable from an
         * ordinary collection: it will read without the "Settling a debt"
         * caption and will offer a Reverse action it should not. Run the
         * migration and both come right for rows created afterwards.
         */
        if (static::receivablesCanRecordSource()) {
            $attributes['source'] = Receivable::SOURCE_DEBT_REPAYMENT;
        }

        $receivable = Receivable::create($attributes);

        // File it in the current period so it is not blank in the "For" column.
        $month = Month::where('name', now()->format('F'))->value('id');
        $year = Year::where('year', now()->year)->value('id');

        if ($month) {
            MonthlyReceivable::create(['receivable_id' => $receivable->id, 'month_id' => $month]);
        }

        if ($year) {
            ReceivableYear::create(['receivable_id' => $receivable->id, 'year_id' => $year]);
        }
    }

    /**
     * A sentence describing what a given repayment will do, shown to the
     * treasurer before they commit to it.
     */
    public function describe(Debt $debt, ?float $amount, bool $fromSavings = false): ?string
    {
        if (! $amount || $amount <= 0) {
            return null;
        }

        $remaining = max(0, (float) $debt->outstanding_balance - $amount);
        $source = $fromSavings ? "deducted from the member's savings" : 'received from the member';

        return $remaining > 0
            ? sprintf(
                'KES %s %s. KES %s will still be owed.',
                number_format($amount, 2),
                $source,
                number_format($remaining, 2),
            )
            : sprintf(
                'KES %s %s. This clears the debt in full.',
                number_format($amount, 2),
                $source,
            );
    }
}
