<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Models\User;
use App\Models\Debt;
use App\Models\Saving;
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
            $account = Account::create($this->extractAccountData($data));

            // Process either general or custom user logic
            if ($data['is_general']) {
                $this->processGeneralAccount($account, $data['amount_due'] ?? 0);
            } else {
                $this->processCustomAccount($account, $customUsersData);
            }

            return $account;
        });
    }

    /**
     * Process account creation for "General" type accounts.
     *
     * @param Account $account
     * @param float $amountDue
     * @return void
     */
    protected function processGeneralAccount(Account $account, float $amountDue): void
    {
        User::all()->each(function ($user) use ($account, $amountDue) {
            $this->attachUserToAccount($account, $user->id, $amountDue);
            $this->createDebt($account->id, $user->id, $amountDue);
            $this->updateSavings($user->id, $amountDue);
        });
    }

    /**
     * Process account creation for "Custom" type accounts.
     *
     * @param Account $account
     * @param array $customUsersData
     * @return void
     */
    protected function processCustomAccount(Account $account, array $customUsersData): void
    {
        foreach ($customUsersData as $customUser) {
            if (isset($customUser['user_id'], $customUser['amount_due'])) {
                $this->attachUserToAccount($account, $customUser['user_id'], $customUser['amount_due']);
                $this->createDebt($account->id, $customUser['user_id'], $customUser['amount_due']);
                $this->updateSavings($customUser['user_id'], $customUser['amount_due']);
            }
        }
    }

    /**
     * Attach a user to an account in the pivot table.
     *
     * @param Account $account
     * @param int $userId
     * @param float $amountDue
     * @return void
     */
    protected function attachUserToAccount(Account $account, int $userId, float $amountDue): void
    {
        $account->users()->attach($userId, ['amount_due' => $amountDue]);
    }

    /**
     * Create a Debt record for a user in an account.
     *
     * @param int $accountId
     * @param int $userId
     * @param float $amountDue
     * @return void
     */
    protected function createDebt(int $accountId, int $userId, float $amountDue): void
    {
        Debt::create([
            'account_id' => $accountId,
            'user_id' => $userId,
            'outstanding_balance' => $amountDue,
        ]);
    }

    /**
     * Extract relevant account data for creation.
     *
     * @param array $data
     * @return array
     */
    protected function extractAccountData(array $data): array
    {
        return collect($data)->only([
            'name', 'frequency_type', 'description', 'is_general', 'billing_type'
        ])->toArray();
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
        $currentBalance = $currentSaving->balance ?? 0.00;
        $currentNetWorth = $currentSaving->net_worth ?? 0.00;

        // Perform calculations for the new savings record
        $newNetWorth = $currentNetWorth - $amountDue;

        // Create new Saving record
        Saving::create([
            'user_id' => $userId,
            'credit_amount' => 0, // No credit for this flow
            'debit_amount' => $amountDue,
            'balance' => $currentBalance,
            'net_worth' => $newNetWorth,
        ]);
    }
}
