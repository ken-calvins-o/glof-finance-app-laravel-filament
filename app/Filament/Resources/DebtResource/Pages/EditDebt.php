<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\DebtResource;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Saving;
use App\Models\AccountCollection;
use Filament\Actions;
use Filament\Notifications\Notification;
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

            // Server-side validation: repayment must not exceed outstanding balance
            if ($newRepaymentAmount > $record->outstanding_balance) {
                Notification::make()
                    ->danger()
                    ->title('Invalid repayment')
                    ->body('Repayment amount cannot exceed the outstanding balance.')
                    ->send();

                throw new \Exception('Repayment amount cannot exceed the outstanding balance.');
            }

            // Preserve key identifiers that might be unintentionally overwritten by fill()
            $accountId = $data['account_id'] ?? $record->account_id;
            $userId = $data['user_id'] ?? $record->user_id;

            // Determine if this debt is a credited loan: no account_id but user has a loan record
            $isCreditedLoan = is_null($accountId) && Loan::where('user_id', $userId)->exists();

            // If it's not a credited loan and account_id is missing, that's an error
            if (is_null($accountId) && !$isCreditedLoan) {
                Notification::make()
                    ->danger()
                    ->title('Missing account')
                    ->body('Account ID is required to record the repayment.')
                    ->send();

                throw new \Exception('Account ID is required to record the repayment.');
            }

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
            // Avoid overwriting calculated/transient fields (like repayment_amount, outstanding_balance, debt_status)
            $fillableData = $data;
            unset(
                $fillableData['repayment_amount'],
                $fillableData['from_savings'],
                $fillableData['outstanding_balance'],
                $fillableData['debt_status']
            );

            $record->fill($fillableData);

            // Ensure account_id and user_id preserved unless explicitly supplied
            $record->account_id = $fillableData['account_id'] ?? $accountId;
            $record->user_id = $fillableData['user_id'] ?? $userId;

            // Reinstate computed fields were already set above (outstanding_balance and debt_status)

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

            // Step 5: Update the AccountCollection amount by incrementing with the repayment
            // Only update account collections when we have an account id (skip for credited loans)
            if (!is_null($accountId)) {
                // Use firstOrCreate and Eloquent's increment to avoid raw SQL and injection risks
                $collection = AccountCollection::firstOrCreate(
                    [
                        'user_id' => $userId,
                        'account_id' => $accountId,
                    ],
                    [
                        'amount' => 0,
                    ]
                );

                // Ensure numeric increment value
                $incrementAmount = (float) $newRepaymentAmount;
                $collection->increment('amount', $incrementAmount);
            }

            // Step 6: Update the Saving model
            $latestSavings = Saving::where('user_id', $userId)->latest()->first();
            $currentBalance = $latestSavings ? $latestSavings->balance : 0;
            $currentNetWorth = $latestSavings ? $latestSavings->net_worth : 0;

            // Step 6.1: Calculate new balance if `from_savings` is true
            $newBalance = $currentBalance;
            if ($fromSavings) {
                $newBalance -= $newRepaymentAmount;

                if ($newBalance < 0) {
                    throw new \Exception("Repayment amount exceeds the user's savings balance.");
                }
            }

            // Step 6.2: Create a new Saving record
            Saving::create([
                'user_id' => $userId,
                'credit_amount' => $fromSavings ? 0 : $newRepaymentAmount,  // Credit only when NOT from savings
                'debit_amount' => $fromSavings ? $newRepaymentAmount : 0,  // Debit only when from savings
                'balance' => $newBalance,
                'net_worth' => $currentNetWorth + $newRepaymentAmount,                           // Keep net worth unchanged
            ]);

            // Step 7: Notify success
            // Resolve display names for user and account (fallbacks in case relationships are missing)
            $userName = $record->user?->name ?? ('User #' . ($record->user_id ?? 'N/A'));
            $accountName = $record->account?->name ?? 'Credited Loan';

            Notification::make()
                ->success()
                ->title('Repayment applied')
                ->body(sprintf('Repayment of Kes %.2f applied for %s on account %s', $newRepaymentAmount, $userName, $accountName))
                ->send();

            // Step 8: Return the updated Debt record
            return $record;
        });
    }
}
