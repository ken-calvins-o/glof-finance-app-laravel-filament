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

    protected function handleRecordCreation(array $data): Receivable
    {
        return DB::transaction(function () use ($data) {
            $membersReceivable = $data['Members Receivable'] ?? [];
            $receivable = null;

            foreach ($membersReceivable as $member) {
                // Extract relevant details
                $userId = $member['user_id'];
                $accountId = $member['account_id'];
                $amountContributed = $member['amount_contributed'];
                $fromSavings = $member['from_savings'] ?? false;

                // Fetch existing Debt record
                $debtRecord = Debt::where('user_id', $userId)
                    ->where('account_id', $accountId)
                    ->first();

                if (!$debtRecord) {
                    throw new \Exception("No associated debt record found for user ID: {$userId} and account ID: {$accountId}.");
                }

                // Calculate new outstanding balance
                $newOutstandingBalance = $debtRecord->outstanding_balance - $amountContributed;

                if ($newOutstandingBalance < 0) {
                    throw new \Exception("The debt's outstanding balance cannot be less than zero for user ID: {$userId} under account ID: {$accountId}.");
                }

                // Update Receivable record first
                $receivable = Receivable::create([
                    'user_id' => $userId,
                    'account_id' => $accountId,
                    'amount_contributed' => $amountContributed,
                    'from_savings' => $fromSavings,
                ]);

                // Update Debt in one step (update outstanding_balance and status)
                $updatedFields = ['outstanding_balance' => $newOutstandingBalance];
                if ($newOutstandingBalance === 0) {
                    $updatedFields['debt_status'] = DebtStatusEnum::Cleared;
                }

                // Apply combined updates
                $debtRecord->update($updatedFields);

                // Update the Savings model
                $this->updateUserSavingsAndNetWorth($userId, $amountContributed, $fromSavings);
            }

            return $receivable;
        });
    }

    protected function updateUserSavingsAndNetWorth($userId, $amountContributed, $fromSavings)
    {
        // Fetch the user's most recent Saving balance (with locking for concurrency safety)
        $lastSaving = Saving::where('user_id', $userId)->latest('id')->lockForUpdate()->first();

        $currentBalance = $lastSaving ? $lastSaving->balance : 0.00;
        $currentNetWorth = $lastSaving ? $lastSaving->net_worth : 0.00;

        // Simplify calculations
        $debitAmount = $fromSavings ? $amountContributed : 0.00;
        $creditAmount = $fromSavings ? 0.00 : $amountContributed;
        $newBalance = $currentBalance - $debitAmount; // Reduce balance if from savings
        $newNetWorth = $currentNetWorth; // Retain net worth if from_savings is true

        if (!$fromSavings) { // Update net worth only if not from savings
            $newNetWorth = $currentNetWorth + $amountContributed;
        }

        // Check for insufficient balance
        if ($newBalance < 0) {
            throw new \Exception("Insufficient balance in savings for user ID: {$userId}. Current Balance: {$currentBalance}, Attempted Debit Amount: {$amountContributed}.");
        }

        // Create the new Saving record (this was not tampered with)
        Saving::create([
            'user_id' => $userId,
            'credit_amount' => $creditAmount,
            'debit_amount' => $debitAmount,
            'balance' => $newBalance,
            'net_worth' => $newNetWorth,
        ]);
    }
}
