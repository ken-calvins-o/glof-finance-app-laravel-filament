<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Filament\Resources\PayableResource;
use App\Models\AccountUser;
use App\Models\Debt;
use App\Models\Saving;
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

            $accountUsers = AccountUser::where('account_id', $accountId)->get();

            foreach ($accountUsers as $accountUser) {
                $outstandingBalance = $accountUser->outstanding_balance;
                $userId = $accountUser->user_id;
                $amountDue = $accountUser->amount_due;
                $amountContributed = $accountUser->amount_contributed ?? 0.00;

                // Fetch the current balance and net worth from the latest saving record for the user
                $latestSavingRecord = Saving::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $currentBalance = $latestSavingRecord->balance ?? 0.00;
                $currentNetWorth = $latestSavingRecord->net_worth ?? 0.00;

                if ($outstandingBalance > 0) {
                    // Inflate outstanding balance by 1% interest
                    $interestAmount = $outstandingBalance * 0.01; // Extract only the interest
                    $newOutstandingBalance = $outstandingBalance + $interestAmount;

                    $accountUser->update(['outstanding_balance' => $newOutstandingBalance]);

                    // Update the user's Debt outstanding balance
                    Debt::where('user_id', $userId)->update(['outstanding_balance' => $newOutstandingBalance]);

                    // Create a saving record for credit amount
                    Saving::create([
                        'user_id' => $userId,
                        'credit_amount' => $newOutstandingBalance,
                        'net_worth' => $currentNetWorth, // Use latest net worth
                        'balance' => $currentBalance
                    ]);

                    // Create a saving record for debit amount and update net worth
                    $totalDebitAmount = $newOutstandingBalance + $amountContributed;

                    Saving::create([
                        'user_id' => $userId,
                        'debit_amount' => $totalDebitAmount,
                        'net_worth' => $currentNetWorth - $interestAmount, // Deduct only the interest amount
                        'balance' => $currentBalance
                    ]);
                }

                $totalAmountDue += $amountDue;
            }

            // Assign the total_amount to create the Payable record
            $data['total_amount'] = $totalAmountDue;

            // Use the parent::handleRecordCreation method to create the Payable record
            return parent::handleRecordCreation($data);
        });
    }
}
