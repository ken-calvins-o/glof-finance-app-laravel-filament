<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Models\User;
use App\Models\Debt;
use App\Models\Saving;
use App\Models\Receivable;
use App\Enums\DebtStatusEnum; // Import the DebtStatusEnum
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected function handleRecordCreation(array $data): Account
    {
        return DB::transaction(function () use ($data) {
            $customUsersData = $data['users'] ?? [];
            unset($data['users']);

            // Create the `Account` record
            $account = Account::create($this->extractAccountData($data));

            // Process data based on `billing_type`
            if (!$data['billing_type']) {
                $this->processBillingTypeFalse($account, $customUsersData);
            } else {
                if ($data['is_general']) {
                    $this->processGeneralAccount($account, $data['amount_due'] ?? 0);
                } else {
                    $this->processCustomAccount($account, $customUsersData);
                }
            }

            return $account;
        });
    }

    /**
     * Handle logic when billing_type is false.
     */
    protected function processBillingTypeFalse(Account $account, array $customUsersData): void
    {
        $pivotData = [];

        foreach ($customUsersData as $customUser) {
            if (isset($customUser['user_id'], $customUser['amount_due'])) {
                $userId = $customUser['user_id'];
                $amountDue = $customUser['amount_due'];

                // Create a Receivable record with the amount_due as the amount_contributed
                $this->createReceivable($account->id, $userId, $amountDue);

                // Update the user's Savings with the credit amount and adjusted net worth
                $this->updateSavingsForBillingTypeFalse($userId, $amountDue);

                // Add to the pivot data for attaching users
                $pivotData[$userId] = ['amount_due' => $amountDue];
            }
        }

        // Attach users to the pivot table
        $account->users()->attach($pivotData);
    }

    /**
     * Create a Receivable record for the user.
     */
    protected function createReceivable(int $accountId, int $userId, float $amountContributed): void
    {
        // Calculate the running total `amount_contributed` for the user in this account
        $totalAmountContributed = Receivable::where('account_id', $accountId)
                ->where('user_id', $userId)
                ->sum('amount_contributed') + $amountContributed;

        // Create the new receivable record
        Receivable::create([
            'account_id' => $accountId,
            'user_id' => $userId,
            'amount_contributed' => $amountContributed,
            'total_amount_contributed' => $totalAmountContributed, // Store the calculated total
        ]);
    }

    /**
     * Update the Savings model for the user when billing_type is false.
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
     * General `is_general` handling when billing_type is true.
     */
    /**
     * General `is_general` handling when billing_type is true.
     */
    protected function processGeneralAccount(Account $account, float $amountDue): void
    {
        $excludedUserIds = request('user_id', []); // Get excluded user IDs from the request (fieldset input)

        $pivotData = [];

        // Get users who are NOT in the excluded users list
        User::whereNotIn('id', $excludedUserIds)->get()->each(function ($user) use ($account, $amountDue, &$pivotData) {
            // Attach the user to the account
            $this->attachUserToAccount($account, $user->id, $amountDue);
            // Create a debt record
            $this->createDebt($account->id, $user->id, $amountDue);
            // Update the user's savings
            $this->updateSavings($user->id, $amountDue);

            // Add to pivot data
            $pivotData[$user->id] = ['amount_due' => $amountDue];
        });

        // Attach all filtered users to the pivot table
        $account->users()->attach($pivotData);
    }

    /**
     * Custom user handling for non-general accounts when billing_type is true.
     */
    protected function processCustomAccount(Account $account, array $customUsersData): void
    {
        $pivotData = [];

        foreach ($customUsersData as $customUser) {
            if (isset($customUser['user_id'], $customUser['amount_due'])) {
                $this->attachUserToAccount($account, $customUser['user_id'], $customUser['amount_due']);
                $this->createDebt($account->id, $customUser['user_id'], $customUser['amount_due']);
                $this->updateSavings($customUser['user_id'], $customUser['amount_due']);

                // Add to pivot data
                $pivotData[$customUser['user_id']] = ['amount_due' => $customUser['amount_due']];
            }
        }

        // Attach all custom users to the pivot table
        $account->users()->attach($pivotData);
    }

    /**
     * Attach a user to an account in the pivot table with amount_due.
     */
    protected function attachUserToAccount(Account $account, int $userId, float $amountDue): void
    {
        $account->users()->attach($userId, ['amount_due' => $amountDue]);
    }

    /**
     * Create a Debt record for a user.
     */
    protected function createDebt(int $accountId, int $userId, float $amount): void
    {
        Debt::create([
            'account_id' => $accountId,
            'user_id' => $userId,
            'amount' => $amount,
            'debt_status' => DebtStatusEnum::Pending, // Set debt_status to Pending
        ]);
    }

    /**
     * Extract account data.
     */
    protected function extractAccountData(array $data): array
    {
        return collect($data)->only([
            'name', 'frequency_type', 'description', 'is_general', 'billing_type', 'create_income',
        ])->toArray();
    }
}
