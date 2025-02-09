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
        return DB::transaction(function () use ($data) {
            // Calculate interest and final balance
            [$interestAmount, $balance] = $this->calculateInterestAndBalance($data);

            // Create the loan record
            $loan = $this->createLoanRecord($data, $balance, $interestAmount);

            // Create Income record if interest is applied
            if ($interestAmount > 0) {
                $this->createIncomeRecord($loan->user_id, $interestAmount);
            }

            // Update user financial records (savings and debts)
            $this->updateUserFinancialRecords($loan);

            return $loan;
        });
    }

    /**
     * Calculate the interest amount and the total balance.
     *
     * @param array $data
     * @return array [interestAmount, balance]
     */
    private function calculateInterestAndBalance(array $data): array
    {
        if ($data['apply_interest']) {
            $interestAmount = $data['amount'] * 0.01; // Interest is 1% of loan amount
            $balance = $data['amount'] + $interestAmount;
        } else {
            $interestAmount = 0;
            $balance = $data['amount'];
        }

        return [$interestAmount, $balance];
    }

    /**
     * Create the loan record in the database.
     *
     * @param array $data
     * @param float $balance
     * @param float $interestAmount
     * @return Loan
     */
    private function createLoanRecord(array $data, float $balance, float $interestAmount): Loan
    {
        // Save the loan record
        return static::getModel()::create(array_merge($data, [
            'balance' => $balance,
            'interest' => $interestAmount,
        ]));
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
            'origin' => 'Loan',
            'interest_amount' => $interestAmount,
        ]);
    }

    /**
     * Update user savings and debts based on the new loan.
     *
     * @param Loan $loan
     * @return void
     */
    private function updateUserFinancialRecords(Loan $loan): void
    {
        // Fetch the most recent saving record for the user
        $currentSaving = Saving::where('user_id', $loan->user_id)
            ->orderBy('created_at', 'desc')
            ->firstOrFail(); // Ensure the latest is fetched

        // Update the savings record
        $this->updateSavings($loan, $currentSaving);

        // Create a debt record for the loan balance
        $this->createDebtRecord($loan->user_id, $loan->balance);
    }

    /**
     * Update the user's saving records to reflect the loan.
     *
     * @param Loan $loan
     * @param Saving $currentSaving
     * @return void
     */
    private function updateSavings(Loan $loan, Saving $currentSaving): void
    {
        $currentNetWorth = $currentSaving->net_worth ?? 0;

        // Use `amount` to reduce net worth unless interest is applied
        $creditAmount = $loan->apply_interest ? $loan->amount + $loan->interest : $loan->amount;

        // Calculate the new net worth
        $newNetWorth = $currentNetWorth - $creditAmount;

        // Save the updated saving record
        Saving::create([
            'user_id' => $loan->user_id,
            'credit_amount' => $loan->amount,
            'debit_amount' => 0,
            'net_worth' => $newNetWorth,
            'balance' => $currentSaving->balance ?? 0,
        ]);
    }

    /**
     * Create a Debt record for the user's outstanding balance on the loan.
     *
     * @param int $userId
     * @param float $outstandingBalance
     * @return void
     */
    private function createDebtRecord(int $userId, float $outstandingBalance): void
    {
        Debt::create([
            'user_id' => $userId,
            'outstanding_balance' => $outstandingBalance,
        ]);
    }
}
