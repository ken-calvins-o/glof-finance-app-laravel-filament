<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Saving;
use App\Models\AccountCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service for handling debt interest calculations and applications.
 * Applies periodic interest to outstanding debts based on configured rate.
 */
class DebtInterestService
{
    /**
     * Default monthly interest rate (1%)
     */
    private const DEFAULT_INTEREST_RATE = 0.01;

    /**
     * Interest rate for calculation
     */
    private float $interestRate;

    public function __construct(?float $interestRate = null)
    {
        $this->interestRate = $interestRate ?? self::DEFAULT_INTEREST_RATE;
        $this->validateInterestRate();
    }

    /**
     * Apply monthly interest to all debts with outstanding balance greater than zero.
     *
     * @return array{processed: int, errors: int, total_interest: float}
     */
    public function applyMonthlyInterest(): array
    {
        $stats = [
            'processed' => 0,
            'errors' => 0,
            'total_interest' => 0.0,
        ];

        try {
            DB::beginTransaction();

            $debts = $this->getDebtsWithOutstandingBalance();
            $netWorthByUser = [];
            $accountAmountByKey = [];

            foreach ($debts as $debt) {
                try {
                    $interest = $this->calculateInterest($debt);
                    $this->updateDebtBalance($debt, $interest);
                    $this->recordInterestInSavings($debt->user_id, $interest, $netWorthByUser);
                    $this->updateAccountCollectionAmount($debt->user_id, $debt->account_id, $interest, $accountAmountByKey);

                    $stats['processed']++;
                    $stats['total_interest'] += $interest;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error(
                        'Failed to apply interest to debt',
                        [
                            'debt_id' => $debt->id,
                            'user_id' => $debt->user_id,
                            'account_id' => $debt->account_id,
                            'error' => $e->getMessage(),
                        ]
                    );
                }
            }

            DB::commit();
            $this->logSummary($stats);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::critical(
                'Critical error in monthly interest application',
                [
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]
            );
            throw $e;
        }

        return $stats;
    }

    /**
     * Retrieve all debts with outstanding balance greater than zero.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getDebtsWithOutstandingBalance()
    {
        return Debt::where('outstanding_balance', '>', 0)
            ->with(['user', 'account'])
            ->get();
    }

    /**
     * Calculate interest amount for a debt.
     *
     * @param Debt $debt
     * @return float
     */
    private function calculateInterest(Debt $debt): float
    {
        return round(
            floatval($debt->outstanding_balance) * $this->interestRate,
            2
        );
    }

    /**
     * Update debt balance by adding interest amount.
     *
     * @param Debt $debt
     * @param float $interest
     * @return void
     */
    private function updateDebtBalance(Debt $debt, float $interest): void
    {
        $previousBalance = floatval($debt->outstanding_balance);
        $newBalance = round($previousBalance + $interest, 2);

        $debt->update([
            'outstanding_balance' => $newBalance,
        ]);

        Log::info(
            'Monthly interest applied to debt',
            [
                'debt_id' => $debt->id,
                'user_id' => $debt->user_id,
                'account_id' => $debt->account_id,
                'previous_balance' => $previousBalance,
                'interest_applied' => $interest,
                'new_balance' => $newBalance,
                'interest_rate' => $this->interestRate * 100 . '%',
            ]
        );
    }

    /**
     * Record interest as a debit in the user's savings and update net worth.
     *
     * @param int $userId
     * @param float $interest
     * @param array<int, float> $netWorthByUser
     * @return void
     */
    private function recordInterestInSavings(int $userId, float $interest, array &$netWorthByUser): void
    {
        if (!array_key_exists($userId, $netWorthByUser)) {
            $netWorthByUser[$userId] = $this->getCurrentNetWorth($userId);
        }

        $newNetWorth = round($netWorthByUser[$userId] - $interest, 2);

        Saving::create([
            'user_id' => $userId,
            'credit_amount' => 0.00,
            'debit_amount' => $interest,
            'balance' => 0.00,
            'net_worth' => $newNetWorth,
        ]);

        $netWorthByUser[$userId] = $newNetWorth;
    }

    /**
     * Get the latest known net worth for a user from savings records.
     */
    private function getCurrentNetWorth(int $userId): float
    {
        return (float) (Saving::where('user_id', $userId)
            ->latest('id')
            ->value('net_worth') ?? 0.0);
    }

    /**
     * Validate interest rate is within acceptable range.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateInterestRate(): void
    {
        if ($this->interestRate < 0 || $this->interestRate > 1) {
            throw new InvalidArgumentException(
                'Interest rate must be between 0 and 1 (0% to 100%)'
            );
        }
    }

    /**
     * Log operation summary.
     *
     * @param array $stats
     * @return void
     */
    private function logSummary(array $stats): void
    {
        Log::info(
            'Monthly interest application completed',
            [
                'debts_processed' => $stats['processed'],
                'errors' => $stats['errors'],
                'total_interest_applied' => $stats['total_interest'],
                'interest_rate' => $this->interestRate * 100 . '%',
            ]
        );
    }

    /**
     * Set custom interest rate.
     *
     * @param float $rate
     * @return $this
     */
    public function setInterestRate(float $rate): self
    {
        $this->interestRate = $rate;
        $this->validateInterestRate();
        return $this;
    }

    /**
     * Get current interest rate.
     *
     * @return float
     */
    public function getInterestRate(): float
    {
        return $this->interestRate;
    }

    /**
     * Update the account collection amount for a user's account by adding interest.
     *
     * @param int $userId
     * @param int|null $accountId
     * @param float $interest
     * @param array<string, array{id:int, amount:float}> $accountAmountByKey
     * @return void
     */
    private function updateAccountCollectionAmount(int $userId, ?int $accountId, float $interest, array &$accountAmountByKey): void
    {
        if (!$accountId) {
            return;
        }

        $key = $userId . ':' . $accountId;

        if (!array_key_exists($key, $accountAmountByKey)) {
            $collection = AccountCollection::where('user_id', $userId)
                ->where('account_id', $accountId)
                ->lockForUpdate()
                ->first();

            if (!$collection) {
                Log::warning('Account collection not found for interest update', [
                    'user_id' => $userId,
                    'account_id' => $accountId,
                ]);
                return;
            }

            $accountAmountByKey[$key] = [
                'id' => $collection->id,
                'amount' => (float) $collection->amount,
            ];
        }

        $newAmount = round($accountAmountByKey[$key]['amount'] - $interest, 2);

        AccountCollection::whereKey($accountAmountByKey[$key]['id'])->update([
            'amount' => $newAmount,
        ]);

        $accountAmountByKey[$key]['amount'] = $newAmount;
    }
}
