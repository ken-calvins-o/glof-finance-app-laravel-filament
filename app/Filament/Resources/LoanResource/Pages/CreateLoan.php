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

    protected static ?string $title = 'Issue a loan';

    public function getSubheading(): ?string
    {
        return 'The member is credited with the money and a repayment record is opened automatically.';
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()->label('Issue loan');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Loan issued';
    }

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
            [$interestAmount, $balance, $rate] = $this->calculateInterestAndBalance($data);

            // Create the loan record
            $loan = $this->createLoanRecord($data, $balance, $rate);

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
     * The `interest` column holds the monthly *rate*, which is how the loans
     * table labels it ("Interest P.M.%") and how the edit page reads it back.
     * Creation used to ignore the rate the treasurer entered, hard-code 1%, and
     * then store the resulting shilling amount in that same column. Editing the
     * loan afterwards read those shillings as a percentage: a KES 50,000 loan
     * stored "500" as its rate, so one save turned a KES 50,500 balance into
     * KES 2,550,000. Storing the rate consistently is what closes that.
     *
     * @param array $data
     * @return array{0: float, 1: float, 2: float} [interestAmount, balance, rate]
     */
    private function calculateInterestAndBalance(array $data): array
    {
        $amount = (float) $data['amount'];
        $applyInterest = (bool) ($data['apply_interest'] ?? false);
        $rate = isset($data['interest']) && is_numeric($data['interest'])
            ? (float) $data['interest']
            : Loan::DEFAULT_MONTHLY_RATE;

        $interestAmount = Loan::interestAmount($amount, $rate, $applyInterest);
        $balance = Loan::repayableAmount($amount, $rate, $applyInterest);

        return [$interestAmount, $balance, $applyInterest ? $rate : 0.0];
    }

    /**
     * Create the loan record in the database.
     *
     * @param array $data
     * @param float $balance
     * @param float $rate The monthly interest rate, as a percentage.
     * @return Loan
     */
    private function createLoanRecord(array $data, float $balance, float $rate): Loan
    {
        // Save the loan record
        return static::getModel()::create(array_merge($data, [
            'balance' => $balance,
            'interest' => $rate,
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
        /*
         * Fetch the most recent saving record for the user.
         *
         * This used to be firstOrFail(), which meant issuing a loan to a member
         * who had no ledger row yet failed with a bare "no query results" page
         * and no indication of what to do about it. A member with no history
         * simply starts from zero.
         */
        $currentSaving = Saving::where('user_id', $loan->user_id)
            ->orderBy('id', 'desc')
            ->first() ?? new Saving(['balance' => 0, 'net_worth' => 0]);

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

        /*
         * The member's net worth drops by everything they will have to repay.
         * That is exactly the loan balance — amount plus interest when interest
         * applies, amount alone when it does not — so reading it off `balance`
         * both matches the previous behaviour and stops this line from adding
         * an interest *rate* to a shilling amount.
         */
        $creditAmount = (float) $loan->balance;

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
