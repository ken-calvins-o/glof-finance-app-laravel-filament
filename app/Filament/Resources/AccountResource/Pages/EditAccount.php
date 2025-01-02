<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;
use App\Models\Debt;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $account, array $data): Model
    {
        return DB::transaction(function () use ($account, $data) {
            // Extract amount_due and AccountUsers from the `$data` array
            $amount_due = $data['amount_due'] ?? 0;
            $accountUsers = $data['AccountUsers'] ?? [];

            // Remove 'amount_due', 'budget', and 'AccountUsers' from `$data` array as those are pivot fields
            unset($data['amount_due'], $data['budget'], $data['AccountUsers']);

            // Update the Account record without the pivot data fields
            $account = parent::handleRecordUpdate($account, $data);

            // Attach users to the account depending on whether it is general or not
            if ($account->is_general) {
                $this->updateUsersInGeneralAccount($account, $amount_due);
            } else {
                $this->updateCustomUsersInAccount($account, $accountUsers);
            }

            return $account;
        });
    }

    protected function updateUsersInGeneralAccount($account, $amount_due): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        $pivotData = [];
        foreach ($users as $user) {
            $pivotData[$user->id] = [
                'amount_due' => $amount_due,
                'balance' => $amount_due,
                'budget' => $amount_due,
            ];

            // Update or create Debt for the user
            Debt::updateOrCreate(
                ['user_id' => $user->id, 'account_id' => $account->id],
                ['balance' => $amount_due]
            );
        }

        $account->users()->sync($pivotData);
    }

    protected function updateCustomUsersInAccount($account, array $accountUsers): void
    {
        foreach ($accountUsers as $accountUser) {
            $userId = $accountUser['user_id'] ?? null;
            $amountDue = $accountUser['amount_due'] ?? 0;

            if ($userId) {
                $pivotData = [
                    'amount_due' => $amountDue,
                    'balance' => $amountDue,
                    'budget' => $amountDue,
                ];

                // Update or create Debt for the user
                Debt::updateOrCreate(
                    ['user_id' => $userId, 'account_id' => $account->id],
                    ['balance' => $amountDue]
                );

                $account->users()->syncWithoutDetaching([$userId => $pivotData]);
            }
        }
    }
}
