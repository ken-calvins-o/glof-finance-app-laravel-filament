<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use App\Enums\DebtStatusEnum;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Saving;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditLoan extends EditRecord
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    /**
     * Handle updating a Loan and keep corresponding Credited Loan Debt in sync.
     *
     * @param \Illuminate\Database\Eloquent\Model $record
     * @param array $data
     * @return Loan
     */
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): Loan
    {
        return DB::transaction(function () use ($record, $data) {
            /** @var Loan $record */

            // Capture old values for delta calculations
            $oldAmount = (float) $record->amount;
            $oldInterest = is_numeric($record->interest) ? (float) $record->interest : 0.0;
            $oldApplyInterest = $record->apply_interest ?? false;
            $oldCreditAmount = $oldApplyInterest ? ($oldAmount + $oldInterest) : $oldAmount;
            $oldBalance = (float) $record->balance;

            // Determine new amount/interest/apply_interest from incoming data OR fallback to existing
            $newAmount = isset($data['amount']) ? (float) $data['amount'] : (float) $record->amount;
            $newInterest = isset($data['interest']) ? (float) $data['interest'] : (is_numeric($record->interest) ? (float) $record->interest : 0.0);
            $newApplyInterest = array_key_exists('apply_interest', $data) ? (bool) $data['apply_interest'] : ($record->apply_interest ?? false);

            // Compute deterministic new balance: if balance provided in payload, prefer it; otherwise compute using interest
            if (array_key_exists('balance', $data) && is_numeric($data['balance'])) {
                $computedBalance = (float) $data['balance'];
            } else {
                if ($newApplyInterest) {
                    // Treat interest as percentage (e.g., 1 => 1%)
                    $computedBalance = $newAmount + ($newAmount * ($newInterest / 100));
                } else {
                    $computedBalance = $newAmount;
                }
            }

            // Round to 2 decimals for money fields
            $computedBalance = round($computedBalance, 2);

            // Update the loan record with incoming data and the computed balance
            $record->fill($data);
            $record->balance = $computedBalance;
            $record->save();

            // Recompute new credit amount after save (consistent with earlier logic)
            $newCreditAmount = $newApplyInterest ? ($newAmount + $newInterest) : $newAmount;
            $newBalance = $computedBalance;

            // Find existing 'Credited Loan' debt for this user (account_id is NULL)
            $debt = Debt::where('user_id', $record->user_id)
                ->whereNull('account_id')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            $debtAction = 'updated';

            if ($debt) {
                // Only update outstanding_balance; preserve existing debt_status unless explicitly changed elsewhere
                $debt->outstanding_balance = $newBalance;
                $debt->save();
            } else {
                // Create a new Debt record representing the credited loan
                $debt = Debt::create([
                    'user_id' => $record->user_id,
                    'account_id' => null,
                    'outstanding_balance' => $newBalance,
                    'debt_status' => DebtStatusEnum::Credited,
                ]);

                $debtAction = 'created';
            }

            // Use user's name in notifications
            $userName = $record->user?->name ?? ('User #' . $record->user_id);

            // Create a Saving adjustment reflecting the delta in credit amount (net_worth change)
            // Delta = newCreditAmount - oldCreditAmount
            $delta = $newCreditAmount - $oldCreditAmount;

            if (abs($delta) > 0.0001) {
                // Fetch latest saving for the user
                $lastSaving = Saving::where('user_id', $record->user_id)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                $currentBalance = $lastSaving?->balance ?? 0.00;
                $currentNetWorth = $lastSaving?->net_worth ?? 0.00;

                // For positive delta (loan increased) net_worth should reduce by delta; for negative delta, net_worth increases
                $newNetWorth = $currentNetWorth - $delta;

                Saving::create([
                    'user_id' => $record->user_id,
                    'credit_amount' => $delta > 0 ? 0.00 : abs($delta),
                    'debit_amount' => $delta > 0 ? $delta : 0.00,
                    // Per request, do NOT alter the saved running 'balance' here
                    'balance' => $currentBalance,
                    'net_worth' => $newNetWorth,
                ]);
            }

            // Consolidated notification: include debt action and loan sync info, visible for 5 seconds
            Notification::make()
                ->success()
                ->title('Loan synchronized')
                ->body(sprintf('Loan amount and balance updated; Credited Loan debt %s to Kes %.2f for %s', $debtAction, $newBalance, $userName))
                ->duration(5)
                ->send();

            return $record;
        });
    }
}
