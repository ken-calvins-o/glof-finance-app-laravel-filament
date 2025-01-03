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

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $accountId = $data['account_id'];
            $totalAmountDue = 0;

            // Retrieve all AccountUser records for the account
            $accountUsers = AccountUser::where('account_id', $accountId)->get();

            foreach ($accountUsers as $accountUser) {
                $userId = $accountUser->user_id;
                $amountDue = $accountUser->amount_due;
                $amountContributed = Receivable::where('account_id', $accountId)
                    ->where('user_id', $userId)
                    ->value('amount_contributed') ?? 0; // `amount_contributed` defaults to 0 if missing

                // Calculate the deficit: amount_due - amount_contributed
                $deficit = $amountDue - $amountContributed;

                if ($deficit > 0) {
                    // Calculate the interest amount (1% of deficit)
                    $interestAmount = $deficit * 0.01;

                    // Add the interest amount to the debt's outstanding balance
                    $debt = Debt::where('user_id', $userId)->first();
                    if ($debt) {
                        // Update the debt's outstanding balance
                        $newOutstandingBalance = $debt->outstanding_balance + $interestAmount;
                        $debt->outstanding_balance = max(0, $newOutstandingBalance); // Ensure balance can't go negative

                        // Update the `debt_status` based on outstanding balance
                        $debt->debt_status = $debt->outstanding_balance > 0
                            ? DebtStatusEnum::Pending // Balance > 0: Pending
                            : DebtStatusEnum::Cleared; // Balance == 0: Cleared

                        $debt->save(); // Save changes to debt
                    }

                    // Fetch the user's latest saving record to update net worth
                    $latestSavingRecord = Saving::where('user_id', $userId)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $currentNetWorth = $latestSavingRecord->net_worth ?? 0.00;
                    $currentBalance = $latestSavingRecord->balance ?? 0.00;

                    // Reduce the net worth by the interest amount only and create a Saving record
                    Saving::create([
                        'user_id' => $userId,
                        'debit_amount' => $deficit + $interestAmount, // Record the total deficit as a debit
                        'net_worth' => $currentNetWorth - $interestAmount, // Deduct only the interest amount
                        'balance' => $currentBalance, // Balance remains unchanged
                    ]);

                    // Create an Income record for the interest
                    Income::create([
                        'user_id' => $userId,
                        'account_id' => $accountId, // Associate with account ID
                        'interest_amount' => $interestAmount, // Record the interest amount as income
                    ]);

                    // Update the receivable record for the user
                    Receivable::create([
                        'user_id' => $userId,
                        'account_id' => $accountId, // Associate with account ID
                        'amount_contributed' => $deficit + $interestAmount,
                        'payment_method' => PaymentMode::Credit_Loan,
                    ]);
                }

                // Add the current amount_due to the totalAmountDue
                $totalAmountDue += $amountDue;
            }

            // Assign the cumulative total_amount_due to the Payable record
            $data['total_amount'] = $totalAmountDue;

            // Use the parent::handleRecordCreation method to save the Payable record
            return parent::handleRecordCreation($data);
        });
    }
}
