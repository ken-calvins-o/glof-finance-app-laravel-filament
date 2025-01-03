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
            // Extract the repeater data from `users`
            $customUsersData = $data['users'] ?? [];
            unset($data['users']);

            // Create the `Account` record
            $account = Account::create([
                'name' => $data['name'],
                'frequency_type' => $data['frequency_type'],
                'description' => $data['description'],
                'is_general' => $data['is_general'],
                'billing_type' => $data['billing_type'],
            ]);

            if ($data['is_general']) {
                // General Account: Attach all users globally with a common `amount_due`
                $amountDue = $data['amount_due'] ?? 0;
                User::all()->each(function ($user) use ($account, $amountDue) {
                    // Insert into the `account_user` pivot table
                    $account->users()->attach($user->id, ['amount_due' => $amountDue]);

                    // Create associated debts for the user
                    Debt::create([
                        'account_id' => $account->id,
                        'user_id' => $user->id,
                        'outstanding_balance' => $amountDue,
                    ]);

                    // Update savings for the user
                    $this->updateSavings($user->id, $amountDue);
                });
            } else {
                // Custom Account: Process each user manually from the repeater data
                foreach ($customUsersData as $customUser) {
                    if (isset($customUser['user_id'], $customUser['amount_due'])) {
                        // Insert into the `account_user` pivot table
                        $account->users()->attach($customUser['user_id'], [
                            'amount_due' => $customUser['amount_due']
                        ]);

                        // Create associated debts for the user
                        Debt::create([
                            'account_id' => $account->id,
                            'user_id' => $customUser['user_id'],
                            'outstanding_balance' => $customUser['amount_due'],
                        ]);

                        // Update savings for the user
                        $this->updateSavings($customUser['user_id'], $customUser['amount_due']);
                    }
                }
            }

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
            // Process and update Saving data for the user
            $this->updateSavings($accountUser->user_id, $accountUser->amount_due);
        }
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
