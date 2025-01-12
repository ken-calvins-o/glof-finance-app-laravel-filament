<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;
use App\Models\Debt;
use App\Models\Saving;
use App\Models\Receivable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $users = $this->record->users()->get();

        $data['users'] = $users->map(function ($user) {
            return [
                'user_id' => $user->id,
                'amount_due' => $user->pivot->amount_due ?? 0,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $account, array $data): Model
    {
        return DB::transaction(function () use ($account, $data) {
            $accountUsers = $data['users'] ?? [];
            unset($data['users']);

            // Update the account data using the parent method
            $account = parent::handleRecordUpdate($account, $data);

            // Process based on account type
            if ($account->is_general) {
                $this->updateUsersInGeneralAccount($account, $data['amount_due'] ?? 0);
            } else {
                $this->updateCustomUsersInAccount($account, $accountUsers, $data['billing_type'] ?? false);
            }

            return $account;
        });
    }

    protected function updateUsersInGeneralAccount($account, $amountDue): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        $pivotData = [];
        foreach ($users as $user) {
            $pivotData[$user->id] = ['amount_due' => $amountDue];

            // Adjust debts, create savings, and update receivables
            $this->adjustDebtCreateSavingsAndReceivablesForUser($account, $user->id, $amountDue);
        }

        $account->users()->sync($pivotData);
    }

    protected function updateCustomUsersInAccount($account, array $accountUsers, bool $billingType): void
    {
        $syncData = [];

        $currentUsersData = $account->users()
            ->withPivot('amount_due')
            ->get()
            ->mapWithKeys(fn($user) => [$user->id => ['amount_due' => $user->pivot->amount_due]])
            ->toArray();

        foreach ($accountUsers as $accountUser) {
            $userId = $accountUser['user_id'] ?? null;
            $amountDue = $accountUser['amount_due'] ?? 0;

            if ($userId) {
                $syncData[$userId] = ['amount_due' => $amountDue];

                if (
                    !isset($currentUsersData[$userId]) || // New user
                    $currentUsersData[$userId]['amount_due'] != $amountDue // Amount changed
                ) {
                    if (!$billingType) {
                        // If billing_type is false, handle receivable and savings creation.
                        $this->createReceivable($account->id, $userId, $amountDue);
                        $this->updateSavingsForBillingTypeFalse($userId, $amountDue);
                    } else {
                        // For billing_type=true, handle debts and savings.
                        $this->adjustDebtCreateSavingsAndReceivablesForUser($account, $userId, $amountDue);
                    }
                }
            }
        }

        $account->users()->sync($syncData);
    }

    /**
     * Adjust Debt, Create Savings, and Create Receivables for the user.
     */
    protected function adjustDebtCreateSavingsAndReceivablesForUser(Model $account, int $userId, float $amountDue): void
    {
        // Update or create debt
        $debt = Debt::where('account_id', $account->id)
            ->where('user_id', $userId)
            ->first();

        if ($debt) {
            if ($debt->outstanding_balance == 0) {
                $debt->update(['outstanding_balance' => $amountDue]);
            } else {
                $debt->update([
                    'outstanding_balance' => $debt->outstanding_balance + $amountDue,
                ]);
            }
        } else {
            Debt::create([
                'account_id' => $account->id,
                'user_id' => $userId,
                'outstanding_balance' => $amountDue,
                'debt_status' => DebtStatusEnum::Pending,
            ]);
        }

        // Create a Receivable record
        $this->createReceivable($account->id, $userId, $amountDue);

        // Create or update a Savings record
        $this->updateSavings($userId, $amountDue);
    }

    /**
     * Create a Receivable record for the user.
     */
    protected function createReceivable(int $accountId, int $userId, float $amountContributed): void
    {
        // Calculate the running total `amount_contributed` for the user
        $totalAmountContributed = Receivable::where('account_id', $accountId)
                ->where('user_id', $userId)
                ->sum('amount_contributed') + $amountContributed;

        // Create the new Receivable record
        Receivable::create([
            'account_id' => $accountId,
            'user_id' => $userId,
            'amount_contributed' => $amountContributed,
            'total_amount_contributed' => $totalAmountContributed,
        ]);
    }

    /**
     * Update Savings for billing_type=false.
     */
    protected function updateSavingsForBillingTypeFalse(int $userId, float $amountDue): void
    {
        $currentSaving = Saving::where('user_id', $userId)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $currentBalance = $currentSaving->balance ?? 0.00;
        $currentDebitAmount = $currentSaving->debit_amount ?? 0.00;
        $currentNetWorth = $currentSaving->net_worth ?? 0.00;

        $newNetWorth = $currentNetWorth + $amountDue;

        Saving::create([
            'user_id' => $userId,
            'credit_amount' => $amountDue,
            'debit_amount' => $currentDebitAmount,
            'balance' => $currentBalance,
            'net_worth' => $newNetWorth,
        ]);
    }

    /**
     * Update Savings for billing_type=true.
     */
    protected function updateSavings(int $userId, float $amountDue): void
    {
        $currentSaving = Saving::where('user_id', $userId)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $currentBalance = $currentSaving->balance ?? 0.00;
        $currentNetWorth = $currentSaving->net_worth ?? 0.00;
        $newNetWorth = $currentNetWorth - $amountDue;

        Saving::create([
            'user_id' => $userId,
            'credit_amount' => 0,
            'debit_amount' => $amountDue,
            'balance' => $currentBalance,
            'net_worth' => $newNetWorth,
        ]);
    }
}
