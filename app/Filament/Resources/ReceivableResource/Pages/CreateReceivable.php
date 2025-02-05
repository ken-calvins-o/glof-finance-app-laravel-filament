<?php

namespace App\Filament\Resources\ReceivableResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\ReceivableResource;
use App\Models\Receivable;
use App\Models\Debt;
use App\Models\Saving;
use App\Models\AccountCollection; // For pivot table
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

                // Create a receivable record first
                $receivable = $this->createReceivableRecord($userId, $accountId, $amountContributed, $fromSavings);

                // Then, update or create the pivot table for AccountCollection
                $this->updateOrCreateAccountCollection($userId, $accountId, $amountContributed, $receivable->id);

                // Fetch and update the related Debt record, if applicable
                $this->updateDebtRecord($userId, $accountId, $amountContributed);

                // Update the Savings record for the user
                $this->updateSavings($userId, $amountContributed, $fromSavings);
            }

            return $receivable;
        });
    }

    /**
     * Create or update the contributed amount in AccountCollection.
     *
     * @param int $userId
     * @param int $accountId
     * @param float $amountContributed
     * @param int $receivableId
     * @return void
     */
    protected function updateOrCreateAccountCollection(int $userId, int $accountId, float $amountContributed, int $receivableId): void
    {
        // Modify or create a new record in the pivot table
        AccountCollection::updateOrCreate(
            [
                'user_id' => $userId,
                'account_id' => $accountId,
            ],
            [
                'amount' => DB::raw("COALESCE(amount, 0) + $amountContributed"),
                'receivable_id' => $receivableId, // Add the receivable_id value here
            ]
        );
    }

    /**
     * Create a new Receivable record.
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
     * Update the Debt record for the specified user and account.
     *
     * @param int $userId
     * @param int $accountId
     * @param float $amountContributed
     * @return void
     */
    protected function updateDebtRecord(int $userId, int $accountId, float $amountContributed): void
    {
        $debt = Debt::where('user_id', $userId)
            ->where('account_id', $accountId)
            ->lockForUpdate()
            ->first();

        if ($debt) {
            $newOutstandingBalance = $debt->outstanding_balance - $amountContributed;

            $debtStatus = $newOutstandingBalance <= 0
                ? DebtStatusEnum::Cleared
                : $debt->debt_status;

            $debt->update([
                'outstanding_balance' => max(0, $newOutstandingBalance),
                'debt_status' => $debtStatus,
            ]);

            if ($debtStatus === DebtStatusEnum::Cleared) {
                // Trigger a notification or event if necessary
            }
        }
    }

    /**
     * Update the user's Savings record.
     *
     * @param int $userId
     * @param float $amountContributed
     * @param bool $fromSavings
     * @return void
     */
    protected function updateSavings(int $userId, float $amountContributed, bool $fromSavings): void
    {
        $lastSaving = Saving::where('user_id', $userId)
            ->latest('id')
            ->lockForUpdate()
            ->first();

        $currentBalance = $lastSaving?->balance ?? 0.00;
        $currentNetWorth = $lastSaving?->net_worth ?? 0.00;

        $debitAmount = $fromSavings ? $amountContributed : 0.00;
        $creditAmount = !$fromSavings ? $amountContributed : 0.00;

        $newNetWorth = $currentNetWorth + $creditAmount;

        Saving::create([
            'user_id' => $userId,
            'credit_amount' => $creditAmount,
            'debit_amount' => $debitAmount,
            'balance' => $currentBalance - $debitAmount,
            'net_worth' => $newNetWorth,
        ]);
    }
}
