<?php

namespace App\Filament\Resources\ReceivableResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\ReceivableResource;
use App\Models\Receivable;
use App\Models\Debt;
use App\Models\Saving;
use App\Models\AccountCollection; // For pivot table
use App\Models\MonthlyReceivable; // Include the MonthlyReceivable model
use App\Models\ReceivableYear;    // Include the ReceivableYear model
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
        // Use a DB transaction for atomicity
        return DB::transaction(function () use ($data) {
            $membersReceivable = $data['Members Receivable'] ?? [];
            $receivable = null;

            foreach ($membersReceivable as $member) {
                $userId = $member['user_id'];
                $accountId = $member['account_id'];
                $amountContributed = $member['amount_contributed'];
                $fromSavings = $member['from_savings'] ?? false;

                // Ensure month_id and year_id are passed for this specific member
                $monthId = $member['month_id'] ?? null;
                $yearId = $member['year_id'] ?? null;

                // Validate that month_id and year_id are provided
                if (!$monthId || !$yearId) {
                    throw new \Exception('Month ID and Year ID must be provided for each member.');
                }

                // Create a Receivable record first
                $receivable = $this->createReceivableRecord($userId, $accountId, $amountContributed, $fromSavings);

                // Add entries to MonthlyReceivable and ReceivableYear models
                $this->createMonthlyReceivable($receivable->id, $monthId);
                $this->createReceivableYear($receivable->id, $yearId);

                // Update or create the pivot table for AccountCollection
                $this->updateOrCreateAccountCollection($userId, $accountId, $amountContributed);

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
     * @return void
     */
    protected function updateOrCreateAccountCollection(int $userId, int $accountId, float $amountContributed): void
    {
        AccountCollection::updateOrCreate(
            [
                'user_id' => $userId,
                'account_id' => $accountId,
            ],
            [
                'amount' => DB::raw("COALESCE(amount, 0) + $amountContributed"),
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
     * Create a new record in the MonthlyReceivable model.
     *
     * @param int $receivableId
     * @param int $monthId
     * @return void
     */
    protected function createMonthlyReceivable(int $receivableId, int $monthId): void
    {
        MonthlyReceivable::create([
            'receivable_id' => $receivableId,
            'month_id' => $monthId,
        ]);
    }

    /**
     * Create a new record in the ReceivableYear model.
     *
     * @param int $receivableId
     * @param int $yearId
     * @return void
     */
    protected function createReceivableYear(int $receivableId, int $yearId): void
    {
        ReceivableYear::create([
            'receivable_id' => $receivableId,
            'year_id' => $yearId,
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
