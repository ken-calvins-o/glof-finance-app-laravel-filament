<?php

namespace App\Filament\Resources\ReceivableResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Enums\PaymentMode;
use App\Filament\Resources\ReceivableResource;
use App\Models\Receivable;
use App\Models\Debt;
use App\Models\Saving;
use App\Models\AccountCollection; // For pivot table
use App\Models\MonthlyReceivable; // Include the MonthlyReceivable model
use App\Models\ReceivableYear;    // Include the ReceivableYear model
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateReceivable extends CreateRecord
{
    protected static string $resource = ReceivableResource::class;

    /**
     * Handle the record creation within a transaction for atomicity.
     * Must return a non-null Eloquent Model as required by Filament's CreateRecord.
     *
     * @param array $data
     * @return Model
     * @throws \Exception
     */
    protected function handleRecordCreation(array $data): Model
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

                // If the entered amount is negative, create/update a Debt with the absolute value,
                // still create and save a Receivable record using the original (negative) amount,
                // update AccountCollection with the negative, create a Saving record to reflect the debit,
                // and show a Filament notification to the user.
                if (is_numeric($amountContributed) && $amountContributed < 0) {
                    $positiveAmount = (float) round(abs($amountContributed), 2);

                    // Update or create the Debt (increment outstanding_balance)
                    $debt = $this->createDebtFromNegativeAmount($userId, $accountId, $positiveAmount, $fromSavings);

                    // Create a Receivable record (this will save negative amounts too)
                    $receivable = $this->createReceivableRecord($userId, $accountId, $amountContributed, $fromSavings);

                    // Add entries to MonthlyReceivable and ReceivableYear models
                    $this->createMonthlyReceivable($receivable->id, $monthId);
                    $this->createReceivableYear($receivable->id, $yearId);

                    // Update AccountCollection so it reflects the negative contribution as well
                    $this->updateOrCreateAccountCollection($userId, $accountId, $amountContributed);

                    // Also create a Saving record reflecting the debt impact:
                    // - credit_amount = 0
                    // - debit_amount = positive amount (absolute value)
                    // - balance reduced by that positive amount
                    // - net_worth reduced by that positive amount
                    $lastSaving = Saving::where('user_id', $userId)
                        ->latest('id')
                        ->lockForUpdate()
                        ->first();

                    $currentBalance = $lastSaving?->balance ?? 0.00;
                    $currentNetWorth = $lastSaving?->net_worth ?? 0.00;

                    Saving::create([
                        'user_id' => $userId,
                        'credit_amount' => 0.00,
                        'debit_amount' => $positiveAmount,
                        // Leave balance unchanged for a debt-recording transaction per user's request
                        'balance' => $currentBalance,
                        'net_worth' => $currentNetWorth - $positiveAmount,
                    ]);

                    // Send a Filament notification to inform the user
                    Notification::make()
                        ->success()
                        ->title('Debt recorded')
                        ->body(sprintf('Converted %s into a debt of Kes %s. Outstanding balance is Kes %s.',
                            number_format($amountContributed, 2),
                            number_format($positiveAmount, 2),
                            number_format($debt->outstanding_balance, 2)
                        ))
                        ->send();

                    // For negative inputs (new debts) do NOT apply repayment logic or extra savings updates
                    continue;
                }

                // Create a Receivable record (this will save negative amounts too)
                $receivable = $this->createReceivableRecord($userId, $accountId, $amountContributed, $fromSavings);

                // Add entries to MonthlyReceivable and ReceivableYear models
                $this->createMonthlyReceivable($receivable->id, $monthId);
                $this->createReceivableYear($receivable->id, $yearId);

                // Update or create the pivot table for AccountCollection
                $this->updateOrCreateAccountCollection($userId, $accountId, $amountContributed);

                // Fetch and update the related Debt record, if applicable (for repayments)
                $this->updateDebtRecord($userId, $accountId, $amountContributed);

                // Update the Savings record for the user
                $this->updateSavings($userId, $amountContributed, $fromSavings);
            }

            // If no Receivable was created (e.g., no members provided),
            // create a minimal Receivable so we always return a Model (Filament requirement).
            if (is_null($receivable)) {
                $first = $membersReceivable[0] ?? null;

                $userId = $first['user_id'] ?? auth()->id();
                $accountId = $first['account_id'] ?? null;
                $monthId = $first['month_id'] ?? null;
                $yearId = $first['year_id'] ?? null;

                $receivable = Receivable::create([
                    'user_id' => $userId,
                    'account_id' => $accountId,
                    'amount_contributed' => 0.00,
                    'from_savings' => false,
                    'payment_method' => PaymentMode::Bank_Transfer,
                ]);

                if ($monthId) {
                    $this->createMonthlyReceivable($receivable->id, $monthId);
                }
                if ($yearId) {
                    $this->createReceivableYear($receivable->id, $yearId);
                }
            }

            return $receivable;
        });
    }

    /**
     * Create a Debt record when a negative contribution is entered.
     * If a debt for same user/account exists, increment its outstanding_balance.
     * Otherwise create a new Debt record.
     *
     * @param int $userId
     * @param int|null $accountId
     * @param float $amountPositive
     * @param bool $fromSavings
     * @return Debt
     */
    protected function createDebtFromNegativeAmount(int $userId, ?int $accountId, float $amountPositive, bool $fromSavings): Debt
    {
        // Try to find existing debt for the user and account
        $debt = Debt::where('user_id', $userId)
            ->where('account_id', $accountId)
            ->lockForUpdate()
            ->first();

        if ($debt) {
            $debt->outstanding_balance = (float) $debt->outstanding_balance + $amountPositive;
            $debt->debt_status = DebtStatusEnum::Pending;
            $debt->save();

            return $debt;
        }

        // No existing debt, create a new one
        return Debt::create([
            'user_id' => $userId,
            'account_id' => $accountId,
            'outstanding_balance' => round($amountPositive, 2),
            'from_savings' => $fromSavings,
            'debt_status' => DebtStatusEnum::Pending,
        ]);
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
        // Ensure we store the exact numeric value the user provided (including negatives)
        if (!is_numeric($amountContributed)) {
            // Coerce to 0.0 if non-numeric (defensive), but ideally this should be validated earlier
            $amountContributed = 0.0;
        }

        $amount = (float) $amountContributed;

        return Receivable::create([
            'user_id' => $userId,
            'account_id' => $accountId,
            'amount_contributed' => $amount,
            'from_savings' => $fromSavings,
            'payment_method' => $fromSavings ? PaymentMode::From_Savings : PaymentMode::Bank_Transfer,
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
     * - Logic changes depending on the value of `from_savings`.
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

        if ($fromSavings) {
            // Logic for when from_savings is true
            Saving::create([
                'user_id' => $userId,
                'credit_amount' => 0.00, // Credit = amount contributed
                'debit_amount' => $amountContributed, // Debit is 0
                'balance' => $currentBalance - $amountContributed, // Deduct from balance
                'net_worth' => $currentNetWorth, // Retain current net worth
            ]);
        } else {
            // Logic for when from_savings is false (existing logic retained)
            Saving::create([
                'user_id' => $userId,
                'credit_amount' => $amountContributed, // Add to credit
                'debit_amount' => 0.00, // Debit is 0
                'balance' => $currentBalance, // Balance remains unchanged
                'net_worth' => $currentNetWorth + $amountContributed, // Increment net worth
            ]);
        }
    }
}
