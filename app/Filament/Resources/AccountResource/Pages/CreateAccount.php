<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Models\MonthlyReceivable;
use App\Models\Receivable;
use App\Models\Debt;
use App\Models\ReceivableYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    /**
     * Custom method to create the Account record.
     */
    protected function handleRecordCreation(array $data): Account
    {
        return DB::transaction(function () use ($data) {
            // Save the Account record
            $account = Account::create([
                'name' => $data['name'],
                'frequency_type' => $data['frequency_type'],
                'description' => $data['description'],
                'is_general' => $data['is_general'],
            ]);

            // Use the provided month_id or fallback to the current month for general accounts
            $monthId = $data['month_id'] ?? now()->format('Ym'); // A dynamic fallback for general accounts
            $yearId = $data['year_id'] ?? now()->format('Y'); // A dynamic fallback for general accounts

            // Handle general or custom account logic
            if ($data['is_general'] === true) {
                $this->processGeneralAccount($account, $data['billing_type'], $data['amount_due'], $monthId, $yearId);
            } else {
                $this->processCustomAccount($account, $data['users'], $monthId, $yearId);
            }

            return $account;
        });
    }

    /**
     * Process logic for general accounts when `is_general` is true.
     */
    protected function processGeneralAccount(Account $account, bool $billingType, float $amountDue, int $monthId, int $yearId): void
    {
        // Retrieve all users
        $users = User::all();

        foreach ($users as $user) {
            // Save the user in the account_user pivot table
            $account->users()->attach($user->id, ['billing_type' => $billingType]);

            // Process receivable or debt based on billing_type
            $this->processBillingType($account, $billingType, $user->id, $amountDue, $monthId, $yearId);
        }
    }

    /**
     * Process logic for custom accounts when `is_general` is false.
     */
    protected function processCustomAccount(Account $account, array $customUsersData, int $fallbackMonthId, int $fallbackYearId): void
    {
        foreach ($customUsersData as $customUser) {
            if (
                isset($customUser['user_id']) &&
                isset($customUser['billing_type']) &&
                isset($customUser['amount_due'])
            ) {
                // Use the month_id from the user data, or fallback to the provided month_id
                $monthId = $customUser['month_id'] ?? $fallbackMonthId;
                $yearId = $customUser['year_id'] ?? $fallbackYearId;

                // Save the user in the account_user pivot table
                $account->users()->attach($customUser['user_id'], [
                    'billing_type' => $customUser['billing_type'],
                ]);

                // Process receivable or debt based on billing_type
                $this->processBillingType(
                    $account,
                    $customUser['billing_type'],
                    $customUser['user_id'],
                    $customUser['amount_due'],
                    $monthId,
                    $yearId,
                );
            }
        }
    }

    /**
     * Process logic based on `billing_type`.
     *
     * @param Account $account
     * @param bool $billingType
     * @param int $userId
     * @param float $amountDue
     * @param int $monthId
     */
    protected function processBillingType(Account $account, bool $billingType, int $userId, float $amountDue, int $monthId, int $yearId): void
    {
        if ($billingType === true) {
            // Case: billing_type is true; this is a Debt.
            $this->createDebt($account, $userId, $amountDue);
        } else {
            // Case: billing_type is false; this is a Receivable.
            $this->createReceivable($account, $userId, $amountDue, $monthId, $yearId);
        }
    }


    /**
     * Create a receivable record and its corresponding MonthlyReceivable record.
     *
     * @param Account $account
     * @param int $userId
     * @param float $amountDue
     * @param int $monthId
     */
    protected function createReceivable(Account $account, int $userId, float $amountDue, int $monthId,int $yearId): void
    {
        // Create the Receivable record
        $receivable = Receivable::create([
            'account_id' => $account->id,
            'user_id' => $userId,
            'amount_contributed' => $amountDue,
            'total_amount_contributed' => $this->getTotalAmountContributed($account->id, $userId, $amountDue),
        ]);

        // Create a MonthlyReceivable record linked to the Receivable
        MonthlyReceivable::create([
            'month_id' => $monthId,
            'receivable_id' => $receivable->id,
        ]);

        ReceivableYear::create([
            'year_id' => $yearId,
            'receivable_id' => $receivable->id,
        ]);

    }

    /**
     * Create a debt record.
     *
     * @param Account $account
     * @param int $userId
     * @param float $amountDue
     */
    protected function createDebt(Account $account, int $userId, float $amountDue): void
    {
        // Create a debt record
        Debt::create([
            'account_id' => $account->id,
            'user_id' => $userId,
            'outstanding_balance' => $amountDue,
        ]);
    }

    /**
     * Helper to calculate the total amount contributed by a user for a specific account.
     *
     * @param int $accountId
     * @param int $userId
     * @param float $currentContribution
     * @return float
     */
    protected function getTotalAmountContributed(int $accountId, int $userId, float $currentContribution): float
    {
        // Compute the sum of all previous contributions for the user in this account
        $existingTotal = Receivable::where('account_id', $accountId)
            ->where('user_id', $userId)
            ->sum('amount_contributed');

        return $existingTotal + $currentContribution;
    }
}
