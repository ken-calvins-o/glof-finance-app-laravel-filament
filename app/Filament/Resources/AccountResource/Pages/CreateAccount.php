<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Models\AccountUser;
use App\Models\Saving;
use App\Models\User;
use App\Models\Debt;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    /**
     * Handle the record creation within a transaction for atomicity.
     *
     * @param array $data
     * @return Account
     */
    protected function handleRecordCreation(array $data): Account
    {
        return DB::transaction(function () use ($data) {
            // Extract and remove 'accountUsers' from input data
            $accountUsersData = $data['accountUsers'] ?? [];
            unset($data['accountUsers']);

            // Create the main Account record
            $account = Account::create([
                'name' => $data['name'],
                'frequency_type' => $data['frequency_type'],
                'description' => $data['description'],
                'is_general' => $data['is_general'],
                'billing_type' => $data['billing_type'],
            ]);

            // Handle user associations based on account type
            if ($data['is_general']) {
                // General Account: Associate all users with a fixed `amount_due`
                $amountDue = $data['amount_due'] ?? 0;
                User::all()->each(function ($user) use ($account, $amountDue) {
                    AccountUser::create([
                        'account_id' => $account->id,
                        'user_id' => $user->id,
                        'amount_due' => $amountDue,
                    ]);
                });
            } else {
                // Custom Account: Create `AccountUser` entries from provided 'accountUsers' data
                foreach ($accountUsersData as $accountUser) {
                    if (isset($accountUser['user_id'], $accountUser['amount_due'])) {
                        AccountUser::create([
                            'account_id' => $account->id,
                            'user_id' => $accountUser['user_id'],
                            'amount_due' => $accountUser['amount_due'],
                        ]);
                    }
                }
            }

            // Assign debts and savings to all associated users
            $this->assignDebtsToAccountUsers($account->id);

            return $account;
        });
    }

    /**
     * Assign Debts and process savings for all users associated with the account.
     *
     * @param int $accountId
     * @return void
     */
    protected function assignDebtsToAccountUsers(int $accountId): void
    {
        // Fetch all AccountUser records linked to this account
        $accountUsers = AccountUser::where('account_id', $accountId)->get();

        foreach ($accountUsers as $accountUser) {
            // Create a new Debt record
            $this->createDebt($accountUser);

            // Process and update Saving data for the user
            $this->updateSavings($accountUser->user_id, $accountUser->amount_due);
        }
    }

    /**
     * Create a Debt record for a specific AccountUser.
     *
     * @param AccountUser $accountUser
     * @return void
     */
    protected function createDebt(AccountUser $accountUser): void
    {
        Debt::create([
            'account_id' => $accountUser->account_id,
            'user_id' => $accountUser->user_id,
            'outstanding_balance' => $accountUser->amount_due,
        ]);
    }

    /**
     * Update Savings and net worth for a specific user.
     *
     * @param int $userId
     * @param float $amountDue
     * @return void
     */
    protected function updateSavings(int $userId, float $amountDue): void
    {
        // Fetch the user's latest savings record with a lock for concurrency safety
        $currentSaving = Saving::where('user_id', $userId)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        // Default the balance and net worth if no previous savings record exists
        $currentBalance = $currentSaving ? $currentSaving->balance : 0.00;
        $currentNetWorth = $currentSaving ? $currentSaving->net_worth : 0.00;

        // Perform calculations for the new savings record
        $newBalance = $currentBalance; // Balance remains unchanged in this flow
        $newNetWorth = $currentNetWorth - $amountDue; // Reduce net worth by the amount_due

        // Create or update the Saving record
        Saving::create([
            'user_id' => $userId,
            'credit_amount' => 0, // No credit for this flow
            'debit_amount' => $amountDue,
            'balance' => $newBalance,
            'net_worth' => $newNetWorth,
        ]);
    }
}
