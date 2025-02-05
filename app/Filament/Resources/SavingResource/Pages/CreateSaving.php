<?php

namespace App\Filament\Resources\SavingResource\Pages;

use App\Filament\Resources\SavingResource;
use App\Models\Saving;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateSaving extends CreateRecord
{
    protected static string $resource = SavingResource::class;

    /**
     * Handle the creation of a new Saving record.
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function handleRecordCreation(array $data): Saving
    {
        return DB::transaction(function () use ($data) {
            $userId = $data['user_id'];
            $creditAmount = $data['credit_amount'];

            // Retrieve the latest saving record for calculating the net worth
            $latestSaving = Saving::where('user_id', $userId)
                ->latest('created_at')
                ->first();

            // Use the existing user's `net_worth` or fallback to 0 if no saving exists
            $previousNetWorth = $latestSaving ? $latestSaving->net_worth : 0;
            $previousBalance = $latestSaving ? $latestSaving->balance : 0;

            // Calculate the new net worth by increasing it with `credit_amount`
            $newNetWorth = $previousNetWorth + $creditAmount;
            $newBalance = $previousBalance + $creditAmount;

            // Create and save the new saving record
            return Saving::create([
                'user_id' => $userId,
                'credit_amount' => $creditAmount,
                'debit_amount' => 0, // Assuming debit_amount remains 0
                'balance' => $newBalance, // Preserve the balance from the latest record
                'net_worth' => $newNetWorth,
            ]);
        });
    }
}
