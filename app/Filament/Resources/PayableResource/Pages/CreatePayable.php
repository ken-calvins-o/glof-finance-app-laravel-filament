<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\PayableResource;
use App\Models\Payable;
use App\Models\PayableYear;
use App\Models\Saving;
use App\Models\Receivable;
use App\Models\MonthlyPayable;
use App\Models\Debt;
use App\Models\Income;
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
            // Handle shared payments
            if ($data['is_general']) {
                $excludedUserIds = $data['user_id'] ?? [];

                // Fetch all users excluding those in the excluded list
                $users = User::whereNotIn('id', $excludedUserIds)->get();

                foreach ($users as $user) {
                    $this->createPayableAndUpdateNetWorth($data, $user, true);
                }

                return new Payable();
            }

            // Custom payments: Iterate over repeater data
            foreach ($data['users'] as $userData) {
                $user = User::findOrFail($userData['user_id']);
                $customData = array_merge($data, $userData); // Merge parent data with user-specific data
                $this->createPayableAndUpdateNetWorth($customData, $user, false);
            }

            return new Payable();
        });

        return new Payable();
    }

    /**
     * Create a Payable, update net worth, and handle Debt and Income logic.
     *
     * @param array $data
     * @param \App\Models\User $user
     * @param bool $isGeneral
     * @return \App\Models\Payable
     */
    protected function createPayableAndUpdateNetWorth(array $data, User $user, bool $isGeneral): Payable
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

            // Step 4: Handle the Debt and Income logic, returning outstanding balance
            $outstandingAmount = $this->handleDebtAndIncome($data['account_id'], $user, $totalAmount);

            // Step 5: Calculate net worth and log the adjustment in Saving model
            $adjustmentAmount = $outstandingAmount > 0 ? $outstandingAmount : $totalAmount;
            $newNetWorth = $saving->net_worth - $adjustmentAmount;

            Saving::create([
                'user_id' => $user->id,
                'credit_amount' => 0, // No credit involved
                'debit_amount' => $adjustmentAmount,
                'balance' => $saving->balance, // Keep balance unchanged unless from_savings applies
                'net_worth' => $newNetWorth,
            ]);

            return $payable;
        });
    }

    /**
     * Automatically create Debt and Income if `total_amount` exceeds `total_amount_contributed`.
     *
     * @param int $accountId
     * @param \App\Models\User $user
     * @param float $totalAmount
     * @return float The outstanding balance (with interest) if a debt is created, 0 otherwise.
     */
    protected function handleDebtAndIncome(int $accountId, User $user, float $totalAmount): float
    {
        // Default "total_amount_contributed" is 0 if no receivable exists
        $latestReceivable = Receivable::where('account_id', $accountId)
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        $totalContributedAmount = $latestReceivable ? $latestReceivable->total_amount_contributed : 0;

        $outstandingBalance = 0;

        // If total_amount exceeds contributed amount, create debt and income
        if ($totalAmount > $totalContributedAmount) {
            $outstandingBalance = $totalAmount - $totalContributedAmount;

            // Add a 1% interest
            $interestAmount = $outstandingBalance * 0.01;
            $outstandingBalance += $interestAmount;

            // Create Debt record
            Debt::create([
                'account_id' => $accountId,
                'user_id' => $user->id,
                'outstanding_balance' => $outstandingBalance,
                'debt_status' => DebtStatusEnum::Pending, // Mark debt as pending
            ]);

            // Create Income record (corresponding to interest)
            Income::create([
                'account_id' => $accountId,
                'user_id' => $user->id,
                'interest_amount' => $interestAmount,
                'income_amount' => 0, // No additional income for now
            ]);
        }

        return $outstandingBalance;
    }
}
