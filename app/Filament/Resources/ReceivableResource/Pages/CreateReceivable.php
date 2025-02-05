<?php

namespace App\Filament\Resources\ReceivableResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\ReceivableResource;
use App\Models\Receivable;
use App\Models\Debt;
use App\Models\Saving;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateReceivable extends CreateRecord
{
    protected static string $resource = ReceivableResource::class;

    /**
     * Handle the record creation within a transaction for atomicity.
     *
     * @param array $data
     * @return Receivable
     * @throws \Exception
     */
    protected function handleRecordCreation(array $data): Receivable
    {
        return DB::transaction(function () use ($data) {
            $membersReceivable = $data['Members Receivable'] ?? [];
            $receivable = null;

            foreach ($membersReceivable as $member) {
                $userId = $member['user_id'];
                $accountId = $member['account_id'];
                $amountContributed = $member['amount_contributed'];
                $fromSavings = $member['from_savings'] ?? false;

                // Fetch the Debt record if it exists
                $debtRecord = $this->getValidatedDebtRecord($userId, $accountId);

                // Use the updated `createReceivableRecord` method
                $receivable = $this->createReceivableRecord($userId, $accountId, $amountContributed, $fromSavings);

                // Update the Debt record, if one exists
                if ($debtRecord) {
                    $this->updateDebtRecordWithCumulativeCheck($debtRecord, $amountContributed, $userId, $accountId);
                }

                // Update the Savings record
                $this->updateUserSavings($userId, $amountContributed, $fromSavings);
            }

            return $receivable;
        });
    }


    /**
     * Fetch the Debt record for a user and account, if it exists.
     *
     * @param int $userId
     * @param int $accountId
     * @return Debt|null
     */
    protected function getValidatedDebtRecord(int $userId, int $accountId): ?Debt
    {
        // Fetch the debt record
        return Debt::where('user_id', $userId)
            ->where('account_id', $accountId)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Create a Receivable record and update the user's total amount contributed.
     *
     * @param int $userId
     * @param int $accountId
     * @param float $amountContributed
     * @param bool $fromSavings
     * @return Receivable
     */
    /**
     * Create a Receivable record and dynamically calculate total_amount_contributed.
     *
     * @param int $userId
     * @param int $accountId
     * @param float $amountContributed
     * @param bool $fromSavings
     * @return Receivable
     */
    protected function createReceivableRecord(int $userId, int $accountId, float $amountContributed, bool $fromSavings): Receivable
    {
        // Get the current total contributions for the account
        $currentTotal = Receivable::where('user_id', $userId)
            ->where('account_id', $accountId)
            ->sum('amount_contributed'); // Sum up all contributions

        // Calculate the new total contribution
        $newTotal = $currentTotal + $amountContributed;

        // Create the new Receivable record
        return Receivable::create([
            'user_id' => $userId,
            'account_id' => $accountId,
            'amount_contributed' => $amountContributed,
            'total_amount_contributed' => $newTotal, // Set the new total contribution
            'from_savings' => $fromSavings,
        ]);
    }

    /**
     * Update a Debt record by checking cumulative "Receivable" contributions.
     *
     * @param Debt $debtRecord
     * @param float $amountContributed
     * @param int $userId
     * @param int $accountId
     * @return void
     */
    protected function updateDebtRecordWithCumulativeCheck(Debt $debtRecord, float $amountContributed, int $userId, int $accountId): void
    {
        $newOutstandingBalance = $debtRecord->outstanding_balance - $amountContributed;

        // Check if the outstanding balance is zero or negative
        $debtStatus = $newOutstandingBalance <= 0 ? DebtStatusEnum::Cleared : $debtRecord->debt_status;

        // Update the Debt record fields
        $debtRecord->update([
            'outstanding_balance' => max(0, $newOutstandingBalance), // Prevent negative balances
            'debt_status' => $debtStatus,
        ]);

        if ($debtStatus === DebtStatusEnum::Cleared) {
            // Trigger any event or notification for clearing debt if necessary
        }
    }

    /**
     * Update the Savings record for a user.
     *
     * @param int $userId
     * @param float $amountContributed
     * @param bool $fromSavings
     * @return void
     * @throws \Exception
     */
    /**
     * Update the Savings record for a user.
     *
     * @param int $userId
     * @param float $amountContributed
     * @param bool $fromSavings
     * @return void
     * @throws \Exception
     */
    protected function updateUserSavings(int $userId, float $amountContributed, bool $fromSavings): void
    {
        // Fetch the user's latest Saving record
        $lastSaving = Saving::where('user_id', $userId)
            ->latest('id')
            ->lockForUpdate()
            ->first();

        // Get the current balance and net worth; defaults to 0 if no previous record exists
        $currentBalance = $lastSaving->balance ?? 0.00;
        $currentNetWorth = $lastSaving->net_worth ?? 0.00;

        // Calculate debit and credit amounts
        $debitAmount = $fromSavings ? $amountContributed : 0.00;
        $creditAmount = !$fromSavings ? $amountContributed : 0.00;

        // Update the net worth only if the contribution is not from savings
        $newNetWorth = $currentNetWorth + $creditAmount;

        // Create a new Saving record with updated details
        Saving::create([
            'user_id' => $userId,
            'credit_amount' => $creditAmount, // New contribution if not from savings
            'debit_amount' => $debitAmount,  // Debit if from savings
            'balance' => $currentBalance,    // Balance remains the same
            'net_worth' => $newNetWorth,     // Increment net worth by credit amount
        ]);
    }
}
