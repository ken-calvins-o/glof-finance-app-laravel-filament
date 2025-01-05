<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use App\Models\Debt;
use App\Models\Income;
use App\Models\Loan;
use App\Models\Saving;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    /**
     * Handle loan record creation with precise operations on related models.
     *
     * @param array $data
     * @return Loan
     */
    protected function handleRecordCreation(array $data): Loan
    {
        // Use a database transaction to ensure atomicity for the entire process
        return DB::transaction(function () use ($data) {
            // Create the loan record in the database
            $loan = static::getModel()::create($data);

            // Calculate the interest amount as 1% of the loan amount
            $interestAmount = $loan->amount * 0.01;

            // Create a new Income record for the loan interest
            $this->createIncomeRecord($loan->user_id, $interestAmount);

            // Retrieve the current Saving record to derive net worth and balance
            $currentSaving = Saving::where('user_id', $loan->user_id)->first();
            $currentNetWorth = $currentSaving->net_worth ?? 0;
            $currentBalance = $currentSaving->balance ?? 0;

            // Create a new Saving record for the user to reflect the loan
            $this->createSavingRecord($loan, $currentNetWorth, $currentBalance);

            // Create a new Debt record for the user with the loan's remaining balance
            $this->createDebtRecord($loan->user_id, $loan->balance);

            return $loan;
        });
    }

    /**
     * Create an Income record for the loan interest.
     *
     * @param int $userId
     * @param float $interestAmount
     * @return void
     */
    private function createIncomeRecord(int $userId, float $interestAmount): void
    {
        Income::create([
            'user_id' => $userId,
            'origin' => 'Loan', // Mark the origin as "Loan"
            'interest_amount' => $interestAmount,
        ]);
    }

    /**
     * Create a Saving record to reflect the loan's impact on net worth.
     *
     * @param Loan $loan
     * @param float $currentNetWorth
     * @param float $currentBalance
     * @return void
     */
    private function createSavingRecord(Loan $loan, float $currentNetWorth, float $currentBalance): void
    {
        Saving::create([
            'user_id' => $loan->user_id,
            'credit_amount' => $loan->amount, // Credit the loan amount
            'debit_amount' => 0, // No debit in this transaction
            'net_worth' => $currentNetWorth - $loan->balance, // Adjust net worth by reducing loan balance
            'balance' => $currentBalance, // Preserve the current balance
        ]);
    }

    /**
     * Create a Debt record to track the user's outstanding balance on the loan.
     *
     * @param int $userId
     * @param float $outstandingBalance
     * @return void
     */
    private function createDebtRecord(int $userId, float $outstandingBalance): void
    {
        Debt::create([
            'user_id' => $userId,
            'outstanding_balance' => $outstandingBalance, // Record the loan's balance
        ]);
    }
}
