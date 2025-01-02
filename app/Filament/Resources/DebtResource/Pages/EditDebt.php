<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\DebtResource;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Saving;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditDebt extends EditRecord
{
    protected static string $resource = DebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): Debt
    {
        return DB::transaction(function () use ($record, $data) {
            /** @var Debt $record */

            // Step 1: Retrieve the new repayment amount
            $newRepaymentAmount = $data['repayment_amount'];
            $fromSavings = $data['from_savings'] ?? false;

            // Step 2: Reduce the outstanding_balance for the Debt record
            $record->outstanding_balance -= $newRepaymentAmount;
            if ($record->outstanding_balance < 0) {
                $record->outstanding_balance = 0; // Ensure balance can't go negative
            }

            // Step 3: Update Debt Status based on the updated balance
            $record->debt_status = $record->outstanding_balance > 0
                ? DebtStatusEnum::Pending   // If balance is more than 0, it’s Pending
                : DebtStatusEnum::Cleared;  // If balance is 0, it's Cleared

            // Save the updated Debt record
            $record->fill($data);
            $record->save();

            // Step 4: Update the Loan balance and handle its status
            $loan = Loan::where('user_id', $record->user_id)->first();
            if ($loan) {
                // Reduce the loan balance by the new repayment amount
                $loan->balance -= $newRepaymentAmount;
                if ($loan->balance < 0) {
                    $loan->balance = 0; // Ensure balance can't go negative
                }

                // If loan balance hits 0, mark its status as Cleared; otherwise, Pending
                $loan->debt_status = $loan->balance > 0
                    ? DebtStatusEnum::Pending
                    : DebtStatusEnum::Cleared;

                $loan->save(); // Save the updated Loan record
            }

            // Step 5: Update the Saving model
            $latestSavings = $record->user->savings()->latest()->first();
            $currentBalance = $latestSavings ? $latestSavings->balance : 0;
            $currentNetWorth = $latestSavings ? $latestSavings->net_worth : 0;

            // Step 5.1: Calculate new balance if `from_savings` is true
            $newBalance = $currentBalance;
            if ($fromSavings) {
                $newBalance -= $newRepaymentAmount;

                if ($newBalance < 0) {
                    throw new \Exception("Repayment amount exceeds the user's savings balance.");
                }
            }

            // Step 5.2: Create a new Saving record
            Saving::create([
                'user_id' => $record->user_id,
                'credit_amount' => $fromSavings ? 0 : $newRepaymentAmount,  // Credit only when NOT from savings
                'debit_amount' => $fromSavings ? $newRepaymentAmount : 0,  // Debit only when from savings
                'balance' => $newBalance,
                'net_worth' => $currentNetWorth,                           // Keep net worth unchanged
            ]);

            // Step 6: Return the updated Debt record
            return $record;
        });
    }
}
