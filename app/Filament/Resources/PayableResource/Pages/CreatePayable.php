<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Enums\PaymentMode;
use App\Filament\Resources\PayableResource;
use App\Models\Payable;
use App\Models\PayableYear;
use App\Models\Saving;
use App\Models\Receivable;
use App\Models\MonthlyPayable;
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
            // Shared Payments
            if ($data['is_general']) {
                $excludedUserIds = $data['user_id'] ?? [];

                // Fetch all users, excluding those from the excluded list
                $users = User::whereNotIn('id', $excludedUserIds)->get();

                foreach ($users as $user) {
                    $this->createPayableAndUpdateNetWorth($data, $user, true);
                }

                return new Payable();
            }

            // Custom Payments: Iterate over repeater data
            foreach ($data['users'] as $userData) {
                $user = User::findOrFail($userData['user_id']);
                $customData = array_merge($data, $userData); // Merge parent form data with specific repeater data
                $this->createPayableAndUpdateNetWorth($customData, $user, false);
            }

            return new Payable();
        });

        return new Payable();
    }

    /**
     * Create Payable and update user net_worth in the Saving model.
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

            // Create Payable record
            $payable = Payable::create([
                'account_id' => $data['account_id'],
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'from_savings' => $data['from_savings'],
                'is_general' => $isGeneral,
            ]);

            // Save the month_id and payable_id in MonthlyPayable
            MonthlyPayable::create([
                'payable_id' => $payable->id,
                'month_id' => $data['month_id'],
            ]);

            // Save the year_id and payable_id in PayableYear
            PayableYear::create([
                'payable_id' => $payable->id,
                'year_id' => $data['year_id'],
            ]);

            // Fetch the latest Saving record for the user
            $saving = Saving::where('user_id', $user->id)
                ->latest('created_at')
                ->first();

            if (!$saving) {
                throw new ModelNotFoundException("No savings record found for user {$user->id}");
            }

            // Reduce net_worth by the total_amount
            $newNetWorth = $saving->net_worth - $totalAmount;

            // Create a new Saving record to log the net_worth adjustment
            Saving::create([
                'user_id' => $user->id,
                'credit_amount' => 0,
                'debit_amount' => $totalAmount,
                'balance' => $saving->balance, // Balance remains unchanged unless from_savings=true
                'net_worth' => $newNetWorth,
            ]);

            return $payable;
        });
    }
}
