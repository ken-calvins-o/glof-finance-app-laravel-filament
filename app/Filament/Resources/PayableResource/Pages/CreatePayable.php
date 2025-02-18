<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Filament\Resources\PayableResource;
use App\Models\AccountCollection;
use App\Models\Payable;
use App\Models\MonthlyPayable;
use App\Models\PayableYear;
use App\Models\Debt;
use App\Models\Saving;
use App\Models\User;
use App\Enums\DebtStatusEnum;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $users = $this->determineUsers($data);

        // Create Payable records for users
        $payables = $this->createPayables($data, $users);

        // Create MonthlyPayable and PayableYear records
        $this->createMonthlyPayables($payables, $data['month_id']);
        $this->createPayableYears($payables, $data['year_id']);

        // Handle debts for users
        $this->handleDebts($data, $users, $payables);

        // Return the last created payable (arbitrary)
        return $payables->last();
    }

    protected function determineUsers(array $data): Collection
    {
        if ($data['is_general']) {
            return $this->getGeneralUsers($data['user_id'] ?? []);
        }

        return $this->getCustomUsers($data['users']);
    }

    protected function getGeneralUsers(array $excludedUserIds): Collection
    {
        return User::whereNotIn('id', $excludedUserIds)->select(['id'])->get(); // Fetch only `id`s
    }

    protected function getCustomUsers(array $customUsers): Collection
    {
        return collect($customUsers);
    }

    protected function createPayables(array $data, Collection $users): Collection
    {
        $payables = collect();
        $isGeneral = $data['is_general'];

        foreach ($users as $user) {
            $userId = $isGeneral ? $user->id : $user['user_id'];
            $fromSavings = $isGeneral ? $data['from_savings'] : ($user['from_savings'] ?? false);
            $totalAmount = $isGeneral ? $data['total_amount'] : ($user['total_amount'] ?? 0);

            // If using savings, deduct balance from user's savings
            if ($fromSavings) {
                $this->deductFromSavings($userId, $totalAmount);
            }

            // Create the Payable record
            $payable = Payable::create([
                'account_id' => $data['account_id'],
                'user_id' => $userId,
                'total_amount' => $totalAmount, // Ensure total_amount is properly handled
                'is_general' => $isGeneral,
                'from_savings' => $fromSavings, // Reflect from_savings choice
            ]);

            $payables->push($payable);
        }

        return $payables;
    }

    protected function createMonthlyPayables(Collection $payables, int $monthId): void
    {
        foreach ($payables as $payable) {
            MonthlyPayable::create([
                'payable_id' => $payable->id,
                'month_id' => $monthId,
            ]);
        }
    }

    protected function createPayableYears(Collection $payables, int $yearId): void
    {
        foreach ($payables as $payable) {
            PayableYear::create([
                'payable_id' => $payable->id,
                'year_id' => $yearId,
            ]);
        }
    }

    protected function handleDebts(array $data, Collection $users, Collection $payables): void
    {
        $isGeneral = $data['is_general'];

        foreach ($users as $user) {
            // Conditional access: Check if user is an array (from repeater, custom users)
            $userId = $isGeneral ? $user->id : $user['user_id'];
            $accountId = $data['account_id'];

            // Retrieve the corresponding payable for the user
            $payable = $payables->firstWhere('user_id', $userId);
            if (!$payable) {
                continue; // Skip if no corresponding payable is found
            }

            $totalAmount = $payable->total_amount;

            // Retrieve (or create) the pivot record from the account_collections table.
            $accountCollection = AccountCollection::firstOrNew([
                'user_id' => $userId,
                'account_id' => $accountId,
            ]);

            // Default the current amount to 0 if not already set.
            $currentAmount = $accountCollection->amount ?? 0;

            // Calculate the deduction and determine if a debt record is needed.
            [$deduction, $outstandingBalance] = $this->calculateDeductionAndInterest($totalAmount, $currentAmount);

            // If there is an outstanding balance, update the Debt record using firstOrNew.
            if ($outstandingBalance > 0) {
                $this->updateDebtRecord($accountId, $userId, $outstandingBalance);
            }

            // ** New Logic: Update Savings **
            $this->updateSavings($userId, $deduction, $outstandingBalance);

            // Update the account collection's amount.
            $accountCollection->amount = $currentAmount - $deduction;
            $accountCollection->save();
        }
    }

    protected function deductFromSavings(int $userId, float $amount): void
    {
        // Retrieve the latest Saving record
        $latestSaving = Saving::where('user_id', $userId)->latest('id')->first();
        $currentBalance = $latestSaving ? $latestSaving->balance : 0;

        // Ensure there is enough balance to deduct
        if ($currentBalance < $amount) {
            throw new \Exception("Member does not have sufficient savings to cover the amount.");
        }

        // Create a new Saving record to reflect the deduction
        Saving::create([
            'user_id' => $userId,
            'credit_amount' => 0, // No credit for deduction
            'debit_amount' => $amount, // Amount deducted
            'balance' => $currentBalance - $amount, // Update balance
            'net_worth' => $latestSaving ? $latestSaving->net_worth - $amount : 0,
        ]);
    }

    /**
     * Calculate the deduction and the outstanding balance (if any) based on totalAmount and currentAmount.
     *
     * @param float|int $totalAmount
     * @param float|int $currentAmount
     * @return array   [deduction, outstandingBalance]
     */
    protected function calculateDeductionAndInterest($totalAmount, $currentAmount): array
    {
        // If current funds are insufficient, compute interest only on the shortfall.
        if ($totalAmount > $currentAmount) {
            $shortfall = $totalAmount - $currentAmount;
            $interest = $shortfall * 0.01;
            $deduction = $totalAmount + $interest;
            $outstandingBalance = $deduction - $currentAmount;
        } else {
            $deduction = $totalAmount;
            $outstandingBalance = 0;
        }

        return [$deduction, $outstandingBalance];
    }


    /**
     * Retrieve (or create) and update the debt record for the given account and user.
     *
     * @param int $accountId
     * @param int $userId
     * @param float|int $outstandingBalance
     * @return void
     */
    protected function updateDebtRecord($accountId, $userId, $outstandingBalance): void
    {
        // Retrieve the Debt model, or create a new one if it doesn't exist.
        $debt = Debt::firstOrNew([
            'account_id' => $accountId,
            'user_id'    => $userId,
        ]);

        // Add the new outstanding balance to the existing one (if any).
        $debt->outstanding_balance += $outstandingBalance;

        // Ensure the debt status remains pending.
        $debt->debt_status = DebtStatusEnum::Pending;
        $debt->save();
    }

    /**
     * Create a new record in the Saving model for the given user.
     *
     * - Fetches the current balance from the latest Saving record for the user.
     * - Creates a new Saving record with updated fields.
     *
     * @param int $userId
     * @param float|int $deduction
     * @param float|int $outstandingBalance
     * @return void
     */
    protected function updateSavings(int $userId, $deduction, $outstandingBalance): void
    {
        // Retrieve the latest Saving record for the user to fetch the current balance
        $latestSaving = Saving::where('user_id', $userId)->latest('id')->first();
        $currentBalance = $latestSaving ? $latestSaving->balance : 0; // Default to 0 if no record exists

        // Create a new Saving record for this operation
        Saving::create([
            'user_id' => $userId,
            'credit_amount' => 0, // Set to 0
            'debit_amount' => $deduction, // Total of total_amount + outstanding_balance
            'balance' => $currentBalance, // Keep the balance the same
            'net_worth' => $latestSaving ? $latestSaving->net_worth - $deduction : 0,
        ]);
    }

}
