<?php

namespace App\Services;

use App\Models\Saving;

class SavingsCalculator
{
    /**
     * Get the current net worth for a user based on their latest savings record.
     *
     * @param int $userId
     * @return float
     */
    public function getCurrentNetWorth(int $userId): float
    {
        return Saving::where('user_id', $userId)
            ->latest('id')
            ->value('net_worth') ?? 0;
    }

    /**
     * Get the current balance for a user based on their latest savings record.
     *
     * @param int $userId
     * @return float
     */
    public function getCurrentBalance(int $userId): float
    {
        return Saving::where('user_id', $userId)
            ->latest('id')
            ->value('balance') ?? 0;
    }

    /**
     * Calculate the new net worth after adding a credit amount.
     *
     * @param float $currentNetWorth
     * @param float $creditAmount
     * @return float
     */
    public function calculateNewNetWorth(float $currentNetWorth, float $creditAmount): float
    {
        return $currentNetWorth + $creditAmount;
    }

    /**
     * Calculate the new balance after adding/subtracting an amount.
     *
     * @param float $currentBalance
     * @param float $creditAmount
     * @param float $debitAmount
     * @return float
     */
    public function calculateBalance(float $currentBalance, float $creditAmount = 0, float $debitAmount = 0): float
    {
        return $currentBalance + $creditAmount - $debitAmount;
    }

    /**
     * Get all form defaults for a user with an optional credit amount.
     *
     * @param int $userId
     * @param float $creditAmount
     * @param float $debitAmount
     * @return array
     */
    public function getFormDefaults(int $userId, float $creditAmount = 0, float $debitAmount = 0): array
    {
        $currentNetWorth = $this->getCurrentNetWorth($userId);
        $currentBalance = $this->getCurrentBalance($userId);

        return [
            'current_net_worth' => $currentNetWorth,
            'current_balance' => $currentBalance,
            'net_worth' => $this->calculateNewNetWorth($currentNetWorth, $creditAmount),
            'balance' => $this->calculateBalance($currentBalance, $creditAmount, $debitAmount),
        ];
    }

    /**
     * Get empty/reset form values.
     *
     * @return array
     */
    public function getResetValues(): array
    {
        return [
            'current_net_worth' => 0,
            'current_balance' => 0,
            'net_worth' => 0,
            'balance' => 0,
        ];
    }
}





