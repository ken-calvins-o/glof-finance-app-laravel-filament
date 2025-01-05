<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Income;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Handle the record creation process.
     *
     * @param array $data
     * @return mixed
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Use a database transaction to ensure atomicity
        return DB::transaction(function () use ($data) {
            // Create the user record
            $user = static::getModel()::create($data);

            // Check if the `registration_fee` field exists in the `$data` array
            if (isset($data['registration_fee']) && $data['registration_fee'] > 0) {
                // Create an Income record using the `registration_fee`
                Income::create([
                    'user_id' => $user->id, // Associate the Income record with the created User
                    'origin' => 'Registration Fee', // Mark the origin as Registration Fee
                    'income_amount' => $data['registration_fee'], // Set the income amount to registration_fee
                ]);
            }

            return $user; // Return the created user
        });
    }
}
