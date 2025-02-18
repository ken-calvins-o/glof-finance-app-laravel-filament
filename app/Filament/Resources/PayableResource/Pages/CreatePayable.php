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
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $users = $this->determineUsers($data);

        // Optimize by bulk inserting Payable records
        $payables = $this->batchCreatePayables($data, $users);

        // Get all created payable IDs
        $payableIds = $payables->pluck('id');

        // Create MonthlyPayable and PayableYear records in bulk
        $this->batchCreateMonthlyPayables($payableIds, $data['month_id']);
        $this->batchCreatePayableYears($payableIds, $data['year_id']);

        // Handle debts for users
        $this->handleDebts($data, $users, $payables);

        // Return the last created payable (arbitrary)
        return $payables->last();
    }

    /**
     * Determine the list of users based on the `is_general` flag.
     *
     * @param array $data
     * @return Collection
     */
    protected function determineUsers(array $data): Collection
    {
        if ($data['is_general']) {
            return $this->getGeneralUsers($data['user_id'] ?? []);
        }

        return $this->getCustomUsers($data['users']);
    }

    /**
     * Get users for a general payment by excluding specific user IDs.
     *
     * @param array $excludedUserIds
     * @return Collection
     */
    protected function getGeneralUsers(array $excludedUserIds): Collection
    {
        return User::whereNotIn('id', $excludedUserIds)->select(['id'])->get(); // Fetch only `id`s
    }

    /**
     * Get users for a custom payment from the repeater data.
     *
     * @param array $customUsers
     * @return Collection
     */
    protected function getCustomUsers(array $customUsers): Collection
    {
        return collect($customUsers);
    }

    /**
     * Batch create Payable records for users.
     *
     * @param array $data
     * @param Collection $users
     * @return Collection
     */
    protected function batchCreatePayables(array $data, Collection $users): Collection
    {
        $isGeneral = $data['is_general'];
        $payables = [];

        foreach ($users as $user) {
            $payables[] = [
                'account_id' => $data['account_id'],
                'user_id' => $isGeneral ? $user->id : $user['user_id'],
                'total_amount' => $isGeneral ? $data['total_amount'] : $user['total_amount'],
                'is_general' => $isGeneral,
                'from_savings' => $isGeneral ? $data['from_savings'] : $user['from_savings'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Perform a single insert query for payables
        Payable::insert($payables);

        // Retrieve recently created payables
        return Payable::whereIn('user_id', $users->pluck('id'))->where('account_id', $data['account_id'])->get();
    }

    /**
     * Batch create MonthlyPayable records.
     *
     * @param Collection $payableIds
     * @param int $monthId
     * @return void
     */
    protected function batchCreateMonthlyPayables(Collection $payableIds, int $monthId): void
    {
        $monthlyPayables = $payableIds->map(function ($payableId) use ($monthId) {
            return [
                'payable_id' => $payableId,
                'month_id' => $monthId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        // Perform a single insert query
        MonthlyPayable::insert($monthlyPayables);
    }

    /**
     * Batch create PayableYear records.
     *
     * @param Collection $payableIds
     * @param int $yearId
     * @return void
     */
    protected function batchCreatePayableYears(Collection $payableIds, int $yearId): void
    {
        $payableYears = $payableIds->map(function ($payableId) use ($yearId) {
            return [
                'payable_id' => $payableId,
                'year_id' => $yearId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        // Perform a single insert query
        PayableYear::insert($payableYears);
    }

    /**
     * Handle debts for users where total_amount is less than the account collection amount.
     *
     * @param array $data
     * @param Collection $users
     * @param Collection $payables
     * @return void
     */
    protected function handleDebts(array $data, Collection $users, Collection $payables): void
    {
        foreach ($users as $user) {
            $userId = $user->id;
            $accountId = $data['account_id'];

            // Retrieve the total_amount from corresponding payable
            $totalAmount = $payables->where('user_id', $userId)->first()->total_amount;

            // Fetch the `amount` from the AccountCollection pivot table
            $accountAmount = $user->accounts()->find($accountId)->pivot->amount ?? 0;

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
