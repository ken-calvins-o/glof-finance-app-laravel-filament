<?php

namespace App\Filament\Resources\SavingResource\Pages;

use App\Filament\Resources\SavingResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Saving;

class CreateSaving extends CreateRecord
{
    protected static string $resource = SavingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calculate the cumulative amount and net worth for the user so far
        $currentAmountBalance = Saving::where('user_id', $data['user_id'])->sum('balance');
        $currentNetWorthBalance = Saving::where('user_id', $data['user_id'])->sum('net_worth');

        // Adding to the cumulative value
        $newAmountBalance = $currentAmountBalance + $data['credit_amount'];
        $newNetWorth = $currentNetWorthBalance + $data['credit_amount'];

        // Store the new values in corresponding columns
        $data['balance'] = $newAmountBalance;
        $data['net_worth'] = $newNetWorth;

        return $data;
    }
}
