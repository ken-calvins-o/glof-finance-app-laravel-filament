<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Enums\PaymentMode;
use App\Filament\Resources\PayableResource;
use App\Models\AccountUser;
use App\Models\MonthlyReceivable;
use App\Models\Payable;
use App\Models\MonthlyPayable;
use App\Models\PayableYear;
use App\Models\Debt;
use App\Models\Receivable;
use App\Models\ReceivableYear;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    protected function handleRecordCreation(array $data): Payable
    {
        return DB::transaction(function () use ($data) {
            return $data['is_general']
                ? $this->createGeneralPayables($data) // Handles general payables
                : $this->createCustomPayables($data); // Handles custom payables from repeater
        });
    }

    /**
     * Handle the creation of general payables for all eligible users.
     *
     * @param array $data
     * @return Payable|null
     */
    protected function createGeneralPayables(array $data): ?Payable
    {
        $users = $this->getEligibleUsers($data['account_id'], $data['user_id'] ?? []); // Filter users to exclude specified ones
        $payable = null;

        foreach ($users as $accountUser) {
            $payable = $this->createPayableRecord($data, $accountUser->user_id); // Create the payable record
            $this->createMonthlyPayable($payable->id, $data['month_id']); // Link to Month
            $this->createPayableYear($payable->id, $data['year_id']);     // Link to Year
            $this->handleDebtCreation($payable, $data['month_id'], $data['year_id']); // Updated with 3 arguments
        }

        return $payable; // Return the last created Payable
    }

    /**
     * Handle the creation of custom payables when is_general is false.
     *
     * @param array $data
     * @return Payable
     */
    protected function createCustomPayables(array $data): Payable
    {
        $customUsers = $data['users'] ?? []; // Extract users from the repeater section
        $payable = null;

        foreach ($customUsers as $user) {
            $payable = $this->createCustomPayableRecord($data, $user['user_id'], $user['total_amount'], $user['from_savings']);
            $this->createMonthlyPayable($payable->id, $data['month_id']); // Link to Month
            $this->createPayableYear($payable->id, $data['year_id']);     // Link to Year
            $this->handleDebtCreation($payable, $data['month_id'], $data['year_id']); // Updated with 3 arguments
        }

        if (!$payable) {
            throw new \Exception("No valid payable was created."); // Ensure we always return a valid Payable
        }

        return $payable; // Return the last created Payable
    }

    /**
     * Handle the creation of a debt record if conditions are met.
     *
     * @param Payable $payable
     * @return void
     */
    protected function handleDebtCreation(Payable $payable, int $monthId, int $yearId): void
    {
        // Get the latest receivable for this account and user
        $latestReceivable = Receivable::where('account_id', $payable->account_id)
            ->where('user_id', $payable->user_id)
            ->latest('id') // Assumes latest is determined by ID or timestamp
            ->first();

        $latestTotalContributed = $latestReceivable->total_amount_contributed ?? 0.00;

        if ($payable->total_amount > $latestTotalContributed) {
            // User accumulates an outstanding balance
            // Calculate outstanding balance with interest
            $outstandingBalance = $this->calculateOutstandingBalance(
                $payable->total_amount,
                $latestTotalContributed
            );

            // Adjust total amount contributed based on outstanding balance
            $contributionAdjustment = $latestTotalContributed - $outstandingBalance;

            // STEP 1: Create Debt record
            Debt::create([
                'account_id' => $payable->account_id,
                'user_id' => $payable->user_id,
                'outstanding_balance' => $outstandingBalance,
            ]);

            // STEP 2: Create Receivable record
            $receivable = Receivable::create([
                'account_id' => $payable->account_id,
                'user_id' => $payable->user_id,
                'amount_contributed' => $outstandingBalance, // Outstanding balance
                'total_amount_contributed' => $contributionAdjustment, // Updated total contributed
                'payment_method' => PaymentMode::Credit_Loan, // Payment mode set as Credit Loan
            ]);

            // STEP 3: Link Receivable to MonthlyReceivable
            MonthlyReceivable::create([
                'month_id' => $monthId, // Use the same month
                'receivable_id' => $receivable->id,
            ]);

            // STEP 4: Link Receivable to ReceivableYear
            ReceivableYear::create([
                'year_id' => $yearId, // Use the same year
                'receivable_id' => $receivable->id,
            ]);
        }
    }
    /**
     * Calculate the outstanding balance with 1% interest.
     *
     * @param float $payableAmount
     * @param float $totalContributed
     * @return float
     */
    protected function calculateOutstandingBalance(float $payableAmount, float $totalContributed): float
    {
        $difference = $payableAmount - $totalContributed;
        $interest = $difference * 0.01; // 1% interest
        return $difference + $interest;
    }

    /**
     * Retrieve eligible users associated with an account, excluding those provided.
     *
     * @param int $accountId
     * @param array $excludedUserIds
     * @return \Illuminate\Support\Collection
     */
    protected function getEligibleUsers(int $accountId, array $excludedUserIds): \Illuminate\Support\Collection
    {
        return AccountUser::where('account_id', $accountId)
            ->whereNotIn('user_id', $excludedUserIds)
            ->get();
    }

    /**
     * Create a Payable record for a specific user.
     *
     * @param array $data
     * @param int $userId
     * @return Payable
     */
    protected function createPayableRecord(array $data, int $userId): Payable
    {
        return Payable::create([
            'account_id' => $data['account_id'],
            'user_id' => $userId,
            'total_amount' => $data['total_amount'],
            'from_savings' => $data['from_savings'],
            'is_general' => $data['is_general'],
        ]);
    }

    /**
     * Create a custom Payable record for a specific user with specific total_amount.
     *
     * @param array $data
     * @param int $userId
     * @param float $totalAmount
     * @param bool $fromSavings
     * @return Payable
     */
    protected function createCustomPayableRecord(array $data, int $userId, float $totalAmount, bool $fromSavings): Payable
    {
        return Payable::create([
            'account_id' => $data['account_id'],
            'user_id' => $userId,
            'total_amount' => $totalAmount,
            'from_savings' => $fromSavings,
            'is_general' => $data['is_general'],
        ]);
    }

    /**
     * Create an entry in the MonthlyPayable pivot table.
     *
     * @param int $payableId
     * @param int $monthId
     * @return void
     */
    protected function createMonthlyPayable(int $payableId, int $monthId): void
    {
        MonthlyPayable::create([
            'payable_id' => $payableId,
            'month_id' => $monthId,
        ]);
    }

    /**
     * Create an entry in the PayableYear pivot table.
     *
     * @param int $payableId
     * @param int $yearId
     * @return void
     */
    protected function createPayableYear(int $payableId, int $yearId): void
    {
        PayableYear::create([
            'payable_id' => $payableId,
            'year_id' => $yearId,
        ]);
    }
}
