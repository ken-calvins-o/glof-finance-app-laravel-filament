<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Filament\Resources\PayableResource;
use App\Models\AccountCollection;
use App\Models\Debt;
use App\Models\Income;
use App\Models\MonthlyPayable;
use App\Models\Payable;
use App\Models\PayableYear;
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
        return DB::transaction(function () use ($data) {
            if ($data['is_general']) {
                $excludedUserIds = $data['user_id'] ?? [];

                // Fetch all users excluding those in the excluded list
                $users = User::whereNotIn('id', $excludedUserIds)->get();

                foreach ($users as $user) {
                    $this->createPayableAndRelatedRecords($data, $user, true);
                }

                return new Payable(); // Return a dummy instantiation if no specific Payable is needed
            }

            // Custom payments: Iterate over repeater data
            foreach ($data['users'] as $userData) {
                $user = User::findOrFail($userData['user_id']);
                $customData = array_merge($data, $userData); // Merge parent data with user data
                $this->createPayableAndRelatedRecords($customData, $user, false);
            }

            return new Payable(); // Return a dummy instantiation
        });
    }

    protected function createPayableAndRelatedRecords(array $data, User $user, bool $isGeneral): void
    {
        DB::transaction(function () use ($data, $user, $isGeneral) {
            $totalAmount = $data['total_amount'];

            // Step 1: Fetch the cumulative amount in AccountCollection
            $existingAmount = AccountCollection::where('account_id', $data['account_id'])
                ->where('user_id', $user->id)
                ->first()?->amount;

            // Determine shortfall and interest
            if ($existingAmount < 0) {
                $shortfall = $totalAmount;
            } else {
                $shortfall = $totalAmount - $existingAmount; // The deficit
            }

            $debtAmount = $totalAmount; // Initialize as the total amount

            if ($shortfall > 0) {
                $interest = $shortfall * 0.01; // 1% interest
                $debtAmount = $shortfall + $interest; // Debt amount to be applied

                // Handle Debt creation/updating
                $debt = Debt::firstOrNew([
                    'account_id' => $data['account_id'],
                    'user_id' => $user->id,
                ]);

                Income::create([
                    'account_id' => $data['account_id'],
                    'user_id' => $user->id,
                    'interest_amount' => $interest,
                ]);

                if ($debt->exists) {
                    $debt->outstanding_balance += $debtAmount; // Add the new amount to existing debt
                } else {
                    $debt->outstanding_balance = $debtAmount;
                }
                $debt->save();

                // Update AccountCollection pivot table
                $newAmount = $existingAmount - $debtAmount; // Deduct the debt amount
                DB::table('account_collections')->updateOrInsert(
                    [
                        'account_id' => $data['account_id'],
                        'user_id' => $user->id,
                    ],
                    [
                        'amount' => $newAmount, // Save the adjusted value
                    ]
                );

                // Update AccountCollection model for accurate persistence
                $accountCollection = AccountCollection::firstOrNew([
                    'account_id' => $data['account_id'],
                    'user_id' => $user->id,
                ]);
                $accountCollection->amount = $newAmount; // Deduct total debt amount
                $accountCollection->save();
            }

            // Step 3: Record the Payable
            $payable = Payable::create([
                'account_id' => $data['account_id'],
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'from_savings' => $data['from_savings'],
                'is_general' => $isGeneral,
            ]);

            // Step 4: Link MonthlyPayable and PayableYear models
            MonthlyPayable::create([
                'payable_id' => $payable->id,
                'month_id' => $data['month_id'],
            ]);
            PayableYear::create([
                'payable_id' => $payable->id,
                'year_id' => $data['year_id'],
            ]);

            // Step 5: Adjust the user’s Savings using the full debt amount (inclusive of interest)
            $this->adjustSavings($user, $debtAmount);
        });
    }

    protected function adjustSavings(User $user, float $debtAmount): void
    {
        $latestSaving = Saving::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        if (!$latestSaving) {
            throw new ModelNotFoundException("Savings record not found for user ID: {$user->id}");
        }

        $newNetWorth = $latestSaving->net_worth - $debtAmount;

        // Record the new Savings adjustment
        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 0,
            'debit_amount' => $debtAmount, // Use the debt amount inclusive of interest
            'balance' => $latestSaving->balance,
            'net_worth' => $newNetWorth, // Deduct the debt amount from net worth
        ]);
    }
}
