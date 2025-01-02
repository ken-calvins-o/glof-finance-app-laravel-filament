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

    // Override the record creation method
    protected function handleRecordCreation(array $data): Loan
    {
        // Use a database transaction to ensure atomicity
        return DB::transaction(function () use ($data) {
            // Create the loan record in the database
            $loan = static::getModel()::create($data);

            // Determine the interest amount (1% of the loan amount)
            $interestAmount = $loan->amount * 0.01;

            // Create a new Income record for the user
            Income::create([
                'user_id' => $loan->user_id,
                'origin' => 'Loan',
                'interest_amount' => $interestAmount,
            ]);

            // Retrieve CURRENT net worth and balance of the user if it exists
            $currentSaving = Saving::where('user_id', $loan->user_id)->first();
            $currentNetWorth = $currentSaving ? $currentSaving->net_worth : 0;

            // Create a new Saving record for the user
            Saving::create([
                'user_id' => $loan->user_id,
                'credit_amount' => $loan->amount, // Set the `credit_amount` to the loan amount
                'debit_amount' => 0, // `debit_amount` remains 0
                'balance' => $currentSaving ? $currentSaving->balance : 0, // Preserve the current balance
                'net_worth' => $currentNetWorth - $loan->balance, // Reduce the current net worth by the loan balance
            ]);

            // Create a new Debt record for the user
            Debt::create([
                'user_id' => $loan->user_id,
                'outstanding_balance' => $loan->balance, // Use the `balance` from the Loan model
            ]);

            return $loan;
        });
    }
}
