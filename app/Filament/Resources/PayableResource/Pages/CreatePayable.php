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
use App\Models\Income;
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

            $payable = Payable::create([
                'account_id' => $data['account_id'],
                'user_id' => $userId,
                'total_amount' => $isGeneral
                    ? $data['total_amount']
                    : ($user['total_amount'] ?? 0), // Ensure total_amount is properly handled
                'is_general' => $isGeneral,
                'from_savings' => $isGeneral
                    ? $data['from_savings']
                    : ($user['from_savings'] ?? 0), // Ensure from_savings has a default
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
            $userId = $isGeneral ? $user->id : $user['user_id'];
            $accountId = $data['account_id'];

            $payable = $payables->firstWhere('user_id', $userId);
            if (!$payable) {
                continue;
            }

            $totalAmount = $payable->total_amount;
            $fromSavings = $payable->from_savings;

            if ($fromSavings) {
                $this->handleSavingsDeduction($userId, $totalAmount);
            } else {
                // Adjusted Logic for from_savings = false
                $accountCollection = AccountCollection::firstOrNew([
                    'user_id' => $userId,
                    'account_id' => $accountId,
                ]);

                // Reset currentAmount for calculation if it's already negative
                $currentAmount = max(0, $accountCollection->amount ?? 0);

                // Correct calculation factoring in reset currentAmount
                [$deduction, $outstandingBalance, $interestAmount] = $this->calculateDeductionAndInterest($totalAmount, $currentAmount);

                if ($outstandingBalance > 0) {
                    $this->updateDebtRecord($accountId, $userId, $outstandingBalance);

                    // Record interest as income when debt is incurred
                    if ($interestAmount > 0) {
                        $this->recordInterestAsIncome($userId, $accountId, $interestAmount);
                    }
                }

                // Subtract deduction from actual AccountCollection amount (not reset amount)
                $accountCollection->amount -= $deduction;
                $accountCollection->save();

                $this->updateSavings($userId, $deduction); // Record savings update
            }
        }
    }

    protected function handleSavingsDeduction(int $userId, float $totalAmount): void
    {
        $latestSaving = Saving::where('user_id', $userId)->latest('id')->first();
        $currentBalance = $latestSaving ? $latestSaving->balance : 0;
        $currentNetWorth = $latestSaving ? $latestSaving->net_worth : 0;

        Saving::create([
            'user_id' => $userId,
            'credit_amount' => 0,
            'debit_amount' => $totalAmount,
            'balance' => $currentBalance - $totalAmount, // Deduct from balance
            'net_worth' => $currentNetWorth, // Net worth remains the SAME
        ]);
    }

    /**
     * Calculate the deduction and the outstanding balance based on totalAmount and currentAmount.
     *
     * Adjusted with logic to reset currentAmount to 0 if it's negative.
     *
     * @param float|int $totalAmount
     * @param float|int $currentAmount
     * @return array   [deduction, outstandingBalance, interestAmount]
     */
    protected function calculateDeductionAndInterest($totalAmount, $currentAmount): array
    {
        // Reset the currentAmount to 0 if it is negative
        $resetCurrentAmount = max(0, $currentAmount);

        if ($totalAmount > $resetCurrentAmount) {
            $shortfall = $totalAmount - $resetCurrentAmount;
            $interest = round($shortfall * 0.01, 2);
            $deduction = $totalAmount + $interest;
            $outstandingBalance = $deduction - $resetCurrentAmount;
        } else {
            $deduction = $totalAmount;
            $outstandingBalance = 0;
            $interest = 0;
        }

        return [$deduction, $outstandingBalance, $interest];
    }


    /**
     * Retrieve (or create) and update the debt record for the given account and user.
     *
     * Adds the outstanding balance incrementally to any existing outstanding balance.
     *
     * @param int $accountId
     * @param int $userId
     * @param float|int $outstandingBalance
     * @return void
     */
    protected function updateDebtRecord($accountId, $userId, $outstandingBalance): void
    {
        $debt = Debt::firstOrNew([
            'account_id' => $accountId,
            'user_id'    => $userId,
        ]);

        $debt->outstanding_balance += $outstandingBalance;
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
     * @return void
     */
    protected function updateSavings(int $userId, $deduction): void
    {
        $latestSaving = Saving::where('user_id', $userId)->latest('id')->first();
        $currentBalance = $latestSaving ? $latestSaving->balance : 0;
        $currentNetWorth = $latestSaving ? $latestSaving->net_worth : 0;

        Saving::create([
            'user_id' => $userId,
            'credit_amount' => 0,
            'debit_amount' => $deduction,
            'balance' => $currentBalance, // Balance remains unchanged for from_savings = false
            'net_worth' => $currentNetWorth - $deduction, // Deduct net worth for from_savings = false
        ]);
    }

    /**
     * Record interest amount as an income record.
     *
     * Creates an Income record capturing the interest charged when a user incurs debt
     * during payable processing.
     *
     * @param int $userId
     * @param int $accountId
     * @param float $interestAmount
     * @return void
     */
    protected function recordInterestAsIncome(int $userId, int $accountId, float $interestAmount): void
    {
        Income::create([
            'user_id' => $userId,
            'account_id' => $accountId,
            'origin' => 'Payable Interest',
            'interest_amount' => $interestAmount,
            'income_amount' => 0,
        ]);
    }

}
