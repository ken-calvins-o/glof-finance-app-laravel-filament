<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Filament\Resources\PayableResource;
use App\Models\Payable;
use App\Models\MonthlyPayable;
use App\Models\PayableYear;
use App\Models\AccountUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    protected function handleRecordCreation(array $data): Payable
    {
        return DB::transaction(function () use ($data) {
            return $data['is_general']
                ? $this->createGeneralPayables($data)
                : $this->createCustomPayable($data);
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
        $users = $this->getEligibleUsers($data['account_id'], $data['user_id'] ?? []);

        foreach ($users as $accountUser) {
            $payable = $this->createPayableRecord($data, $accountUser->user_id);
            $this->createMonthlyPayable($payable->id, $data['month_id']);
            $this->createPayableYear($payable->id, $data['year_id']);
        }

        // Return the first payable (or null if no users were added)
        return $payable ?? null;
    }

    /**
     * Handle the creation of a custom payable.
     *
     * @param array $data
     * @return Payable
     */
    protected function createCustomPayable(array $data): Payable
    {
        // You can extend this method to handle custom payables as needed
        return Payable::create([
            'account_id' => $data['account_id'],
            'is_general' => $data['is_general'],
        ]);
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
