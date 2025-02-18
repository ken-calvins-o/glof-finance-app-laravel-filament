<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Filament\Resources\PayableResource;
use App\Models\Payable;
use App\Models\MonthlyPayable;
use App\Models\PayableYear;
use App\Models\Debt;
use App\Models\User;
use App\Enums\DebtStatusEnum;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $users = $this->determineUsers($data);

        // Create Payable records for users
        $payables = $this->createPayables($data, $users);

        // Create MonthlyPayable and PayableYear records
        $this->createMonthlyPayables($payables, $data['month_id']);
        $this->createPayableYears($payables, $data['year_id']);

        // Handle debts for users
        $this->handleDebts($data, $users, $payables);

        // Return the last created payable (arbitrary)
        return $payables->last();
    }

    protected function determineUsers(array $data): Collection
    {
        if ($data['is_general']) {
            return $this->getGeneralUsers($data['user_id'] ?? []);
        }

        return $this->getCustomUsers($data['users']);
    }

    protected function getGeneralUsers(array $excludedUserIds): Collection
    {
        return User::whereNotIn('id', $excludedUserIds)->select(['id'])->get(); // Fetch only `id`s
    }

    protected function getCustomUsers(array $customUsers): Collection
    {
        return collect($customUsers);
    }

    protected function createPayables(array $data, Collection $users): Collection
    {
        $payables = collect();
        $isGeneral = $data['is_general'];

        foreach ($users as $user) {
            $payable = Payable::create([
                'account_id' => $data['account_id'],
                'user_id' => $isGeneral ? $user->id : $user['user_id'],
                'total_amount' => $isGeneral
                    ? $data['total_amount']
                    : ($user['total_amount'] ?? 0), // Ensure total_amount is properly handled
                'is_general' => $isGeneral,
                'from_savings' => $isGeneral
                    ? $data['from_savings']
                    : ($user['from_savings'] ?? 0), // Ensure from_savings has a default
            ]);

            $payables->push($payable);
        }

        return $payables;
    }

    protected function createMonthlyPayables(Collection $payables, int $monthId): void
    {
        foreach ($payables as $payable) {
            MonthlyPayable::create([
                'payable_id' => $payable->id,
                'month_id' => $monthId,
            ]);
        }
    }

    protected function createPayableYears(Collection $payables, int $yearId): void
    {
        foreach ($payables as $payable) {
            PayableYear::create([
                'payable_id' => $payable->id,
                'year_id' => $yearId,
            ]);
        }
    }

    protected function handleDebts(array $data, Collection $users, Collection $payables): void
    {
        foreach ($users as $user) {
            $userId = $user->id;
            $accountId = $data['account_id'];

            // Retrieve the total_amount from corresponding payable
            $totalAmount = $payables->where('user_id', $userId)->first()->total_amount;

            // Fetch the `amount` from the AccountCollection pivot table
            $accountAmount = $user->accounts()->find($accountId)?->pivot->amount ?? 0;

            // Check if total_amount is greater than the accountAmount
            if ($totalAmount > $accountAmount) {
                $excess = $totalAmount - $accountAmount;
                $interest = $excess * 0.01; // Calculate 1% interest
                $outstandingBalance = $excess + $interest;

                // Create or update the Debt record
                Debt::updateOrCreate(
                    ['account_id' => $accountId, 'user_id' => $userId],
                    [
                        'outstanding_balance' => $outstandingBalance,
                        'debt_status' => DebtStatusEnum::Pending,
                    ]
                );
            }
        }
    }
}
