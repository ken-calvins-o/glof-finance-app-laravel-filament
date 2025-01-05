<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Enums\PaymentMode;
use App\Filament\Resources\PayableResource;
use App\Models\AccountUser;
use App\Models\Debt;
use App\Models\Saving;
use App\Models\Receivable;
use App\Models\Income; // Include the Income model
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    /**
     * Handle the record creation within a database transaction.
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $accountId = $data['account_id'];
            $totalAmountDue = 0;

            // Retrieve all AccountUser records for the given account
            $accountUsers = AccountUser::where('account_id', $accountId)->get();

            foreach ($accountUsers as $accountUser) {
                $userId = $accountUser->user_id;
                $amountDue = $accountUser->amount_due;

                // Look up the previously contributed amount (default to 0 if absent)
                $amountContributed = Receivable::where('account_id', $accountId)
                    ->where('user_id', $userId)
                    ->value('amount_contributed') ?? 0;

                // Calculate the deficit
                $deficit = $amountDue - $amountContributed;

                if ($deficit > 0) {
                    // Calculate the interest as 1% of the deficit
                    $interestAmount = $deficit * 0.01;

                    // Update the user's debt record
                    $this->updateDebt($userId, $interestAmount);

                    // Create the savings record
                    $this->createSavingsRecord($userId, $interestAmount);

                    // Create the income record
                    $this->createIncomeRecord($accountId, $userId, $interestAmount);

                    // Create the receivable record
                    $this->createReceivableRecord($accountId, $userId, $deficit, $interestAmount);
                }

                // Add the amount_due to the totalAmountDue
                $totalAmountDue += $amountDue;
            }

            // Assign the total amount to the Payable record
            $data['total_amount'] = $totalAmountDue;

            // Use parent handleRecordCreation to save the Payable record
            return parent::handleRecordCreation($data);
        });
    }

    /**
     * Update the debt record for a user.
     *
     * @param int $userId
     * @param float $interestAmount
     * @return void
     */
    private function updateDebt(int $userId, float $interestAmount): void
    {
        $debt = Debt::where('user_id', $userId)->first();

        if ($debt) {
            // Update the debt's outstanding balance and status
            $debt->outstanding_balance = max(0, $debt->outstanding_balance + $interestAmount);

            $debt->debt_status = $debt->outstanding_balance > 0
                ? DebtStatusEnum::Pending
                : DebtStatusEnum::Cleared;

            $debt->save();
        }
    }

    /**
     * Create a savings record for a user.
     *
     * @param int $userId
     * @param float $interestAmount
     * @return void
     */
    private function createSavingsRecord(int $userId, float $interestAmount): void
    {
        // Fetch the user's latest saving record
        $latestSavingRecord = Saving::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        $currentNetWorth = $latestSavingRecord->net_worth ?? 0.00;
        $currentBalance = $latestSavingRecord->balance ?? 0.00;

        // Create a new saving record
        Saving::create([
            'user_id' => $userId,
            'debit_amount' => $interestAmount,
            'net_worth' => $currentNetWorth - $interestAmount,
            'balance' => $currentBalance, // No change to the current balance
        ]);
    }

    /**
     * Create an income record for a user.
     *
     * @param int $accountId
     * @param int $userId
     * @param float $interestAmount
     * @return void
     */
    private function createIncomeRecord(int $accountId, int $userId, float $interestAmount): void
    {
        Income::create([
            'user_id' => $userId,
            'account_id' => $accountId,
            'interest_amount' => $interestAmount,
        ]);
    }

    /**
     * Create a receivable record for a user.
     *
     * @param int $accountId
     * @param int $userId
     * @param float $deficit
     * @param float $interestAmount
     * @return void
     */
    private function createReceivableRecord(
        int $accountId,
        int $userId,
        float $deficit,
        float $interestAmount
    ): void {
        Receivable::create([
            'user_id' => $userId,
            'account_id' => $accountId,
            'amount_contributed' => $deficit + $interestAmount,
            'payment_method' => PaymentMode::Credit_Loan,
        ]);
    }
}
