<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\PayableResource;
use App\Models\AccountCollection;
use App\Models\Debt;
use App\Models\Income;
use App\Models\MonthlyPayable;
use App\Models\Payable;
use App\Models\PayableYear;
use App\Models\Receivable;
use App\Models\Saving;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        DB::transaction(function () use ($data) {
            if ($data['is_general']) {
                $excludedUserIds = $data['user_id'] ?? [];

                // Fetch all users excluding those in the excluded list
                $users = User::whereNotIn('id', $excludedUserIds)->get();

                foreach ($users as $user) {
                    $this->createPayableAndHandleDebts($data, $user, true);
                }

                return new Payable();
            }

            // Custom payments: Iterate over repeater data
            foreach ($data['users'] as $userData) {
                $user = User::findOrFail($userData['user_id']);
                $customData = array_merge($data, $userData); // Merge parent data with user data
                $this->createPayableAndHandleDebts($customData, $user, false);
            }

            return new Payable();
        });

        return new Payable();
    }

    /**
     * Create a Payable, handle debts selectively, and update the Savings and AccountCollection models.
     *
     * @param array $data
     * @param \App\Models\User $user
     * @param bool $isGeneral
     * @return \App\Models\Payable
     */
    protected function createPayableAndHandleDebts(array $data, User $user, bool $isGeneral): Payable
    {
        return DB::transaction(function () use ($data, $user, $isGeneral) {
            $totalAmount = $data['total_amount'];

            // Step 1: Create the Payable
            $payable = Payable::create([
                'account_id' => $data['account_id'],
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'from_savings' => $data['from_savings'],
                'is_general' => $isGeneral,
            ]);

            // Step 2: Associate Payable with MonthlyPayable and PayableYear
            MonthlyPayable::create([
                'payable_id' => $payable->id,
                'month_id' => $data['month_id'],
            ]);
            PayableYear::create([
                'payable_id' => $payable->id,
                'year_id' => $data['year_id'],
            ]);

            // Step 3: Retrieve the latest Saving record
            $saving = Saving::where('user_id', $user->id)
                ->latest('created_at')
                ->first();

            if (!$saving) {
                throw new ModelNotFoundException("No savings record found for user {$user->id}");
            }

            // Step 4: Check if debt needs to be created and handle outstanding balances
            $outstandingAmount = $this->checkAndCreateDebtAndIncome($data['account_id'], $user, $totalAmount);

            // Step 5: Update AccountCollection to reduce amount or create a new record
            $this->updateOrCreateAccountCollection($data['account_id'], $user, $totalAmount, $outstandingAmount);

            // Step 6: Adjust the Savings model based on new calculations
            $adjustmentAmount = $outstandingAmount > 0 ? $outstandingAmount : $totalAmount;
            $newNetWorth = $saving->net_worth - $adjustmentAmount;

            Saving::create([
                'user_id' => $user->id,
                'credit_amount' => 0,
                'debit_amount' => $adjustmentAmount,
                'balance' => $saving->balance,
                'net_worth' => $newNetWorth,
            ]);

            return $payable;
        });
    }

    /**
     * Update or create an AccountCollection record to reduce the amount.
     *
     * @param int $accountId
     * @param \App\Models\User $user
     * @param float $totalAmount
     * @param float|null $outstandingAmount
     * @return void
     */
    protected function updateOrCreateAccountCollection(int $accountId, User $user, float $totalAmount, ?float $outstandingAmount): void
    {
        $accountCollection = AccountCollection::where('account_id', $accountId)
            ->where('user_id', $user->id)
            ->first();

        if ($accountCollection) {
            // Update the amount by subtracting the total amount
            $accountCollection->update([
                'amount' => $accountCollection->amount - $totalAmount,
            ]);
        } else {
            // Calculate interest if outstanding balance exists
            $interestAmount = ($outstandingAmount !== null && $outstandingAmount > 0) ? $outstandingAmount * 0.01 : 0;

            // Create a new record with a negative amount (inclusive of interest, if applicable)
            AccountCollection::create([
                'account_id' => $accountId,
                'user_id' => $user->id,
                'amount' => -($totalAmount + $interestAmount),
            ]);
        }
    }

    /**
     * Check conditions for Debt creation and handle both Debt and Income logic.
     *
     * @param int $accountId
     * @param \App\Models\User $user
     * @param float $totalAmount
     * @return float The outstanding balance (including interest) if Debt is created, 0 otherwise.
     */
    protected function checkAndCreateDebtAndIncome(int $accountId, User $user, float $totalAmount): float
    {
        $accountCollection = DB::table('account_collections')
            ->where('account_id', $accountId)
            ->where('user_id', $user->id)
            ->first();

        $totalContributedAmount = $accountCollection ? $accountCollection->amount : 0;

        $outstandingBalance = 0;

        if ($totalAmount > $totalContributedAmount) {
            $outstandingBalance = $totalAmount - $totalContributedAmount;

            $interestAmount = $outstandingBalance * 0.01;
            $outstandingBalance += $interestAmount;

            Debt::create([
                'account_id' => $accountId,
                'user_id' => $user->id,
                'outstanding_balance' => $outstandingBalance,
                'debt_status' => DebtStatusEnum::Pending,
            ]);

            Income::create([
                'account_id' => $accountId,
                'user_id' => $user->id,
                'interest_amount' => $interestAmount,
                'income_amount' => 0,
            ]);
        }

        return $outstandingBalance;
    }
}
