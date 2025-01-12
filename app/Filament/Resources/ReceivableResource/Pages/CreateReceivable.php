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

                // Fetch and validate the debt record
                $debtRecord = $this->getValidatedDebtRecord($userId, $accountId, $amountContributed);

                // Create the Receivable
                $receivable = $this->createReceivableRecord($userId, $accountId, $amountContributed, $fromSavings);

                // Update the Debt record with cumulative handling
                $this->updateDebtRecordWithCumulativeCheck($debtRecord, $amountContributed, $userId, $accountId);

                // Update the Savings record
                $this->updateUserSavings($userId, $amountContributed, $fromSavings);
            }

            return $receivable;
        });
    }

    /**
     * Fetch and validate the Debt record for a user and account.
     *
     * @param int $userId
     * @param int $accountId
     * @param float $amountContributed
     * @return Debt
     * @throws \Exception
     */
    protected function getValidatedDebtRecord(int $userId, int $accountId, float $amountContributed): Debt
    {
        $debtRecord = Debt::where('user_id', $userId)
            ->where('account_id', $accountId)
            ->first();

        if (!$debtRecord) {
            throw new \Exception("No associated debt record found for user ID: {$userId} and account ID: {$accountId}.");
        }

        return $debtRecord;
    }

    /**
     * Create a Receivable record.
     *
     * @param int $userId
     * @param int $accountId
     * @param float $amountContributed
     * @param bool $fromSavings
     * @return Receivable
     */
    protected function createReceivableRecord(int $userId, int $accountId, float $amountContributed, bool $fromSavings): Receivable
    {
        return Receivable::create([
            'user_id' => $userId,
            'account_id' => $accountId,
            'amount_contributed' => $amountContributed,
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

        // Calculate the cumulative amount_contributed for this user and account from Receivables
        $cumulativeContributions = Receivable::where('user_id', $userId)
            ->where('account_id', $accountId)
            ->sum('amount_contributed');

        // Update Debt record fields
        $updatedFields = ['outstanding_balance' => $newOutstandingBalance];

        // If cumulative contributions exceed or match the original outstanding balance, clear the debt
        if ($cumulativeContributions >= $debtRecord->outstanding_balance) {
            $updatedFields['debt_status'] = DebtStatusEnum::Cleared;
        }

        // Apply combined updates
        $debtRecord->update($updatedFields);
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
    protected function updateUserSavings(int $userId, float $amountContributed, bool $fromSavings): void
    {
        $lastSaving = Saving::where('user_id', $userId)
            ->latest('id')
            ->lockForUpdate()
            ->first();

        $currentBalance = $lastSaving->balance ?? 0.00;
        $currentNetWorth = $lastSaving->net_worth ?? 0.00;

        $debitAmount = $fromSavings ? $amountContributed : 0.00;
        $creditAmount = $fromSavings ? 0.00 : $amountContributed;
        $newBalance = $currentBalance - $debitAmount;
        $newNetWorth = $currentNetWorth;

        if (!$fromSavings) {
            $newNetWorth += $amountContributed;
        }
//
//        if ($newBalance < 0) {
//            throw new \Exception("Insufficient balance in savings for user ID: {$userId}. Current Balance: {$currentBalance}, Attempted Debit Amount: {$amountContributed}.");
//        }

        Saving::create([
            'user_id' => $userId,
            'credit_amount' => $creditAmount,
            'debit_amount' => $debitAmount,
            'balance' => $newBalance,
            'net_worth' => $newNetWorth,
        ]);
    }
}
