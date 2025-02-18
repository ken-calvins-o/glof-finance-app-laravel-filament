<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Income;
use App\Models\Saving;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password');

        // Seed users and savings
        $users = [
            [ 'name' => 'John Owegi', 'email' => 'admin@glof.co.ke', 'registration_fee' => 1000, 'password' => $defaultPassword, 'role' => RoleEnum::Administrator ],
            [ 'name' => 'Jorim Nyamor', 'registration_fee' => 1000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Nelson Omolo', 'registration_fee' => 1000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'George Ochodo', 'registration_fee' => 1000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Patrick Digolo', 'registration_fee' => 1000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Jim Oyugi', 'registration_fee' => 1000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Hezborne Onyango', 'registration_fee' => 1000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Booker Odenyo', 'registration_fee' => 1000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Dick Otieno', 'registration_fee' => 1000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Tom Atak', 'registration_fee' => 3000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Ibrahim Onyata', 'registration_fee' => 5000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Sam Amenya', 'registration_fee' => 5000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Norbert Opiyo', 'registration_fee' => 5000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Jack Okuku', 'registration_fee' => 5000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Martin Odipo', 'registration_fee' => 5000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Eliud Adiedo', 'registration_fee' => 10000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Cornel Opiyo', 'registration_fee' => 10000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Francis Raudo', 'registration_fee' => 10000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Don Riaroh', 'registration_fee' => 10000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Jotham Arwa', 'registration_fee' => 10000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Maurice KAnjejo', 'registration_fee' => 10000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Victor Denge', 'registration_fee' => 10000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Chris Onyango', 'registration_fee' => 10000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Maurice Owiti', 'registration_fee' => 20000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'William Osewe', 'registration_fee' => 20000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Jerim Otieno', 'registration_fee' => 20000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Frederick Otieno', 'registration_fee' => 20000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Daniel Olago', 'registration_fee' => 20000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Nicholas Akech', 'registration_fee' => 20000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Dr. Rae', 'registration_fee' => 20000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Cosmas Ngeso', 'registration_fee' => 20000, 'password' => null, 'role' => RoleEnum::Member ],
            [ 'name' => 'Ambrose Anguka', 'registration_fee' => 20000, 'password' => null, 'role' => RoleEnum::Member ],
        ];

        // Create each user and the corresponding saving record
        foreach ($users as $userData) {
            $user = User::create($userData);

            // Create a saving record using the registration fee as credit_amount
            Saving::create([
                'user_id'       => $user->id,
                'credit_amount' => $userData['registration_fee'],
                'debit_amount'  => 0,
                'net_worth'     => 0,
            ]);

            Income::create([
                'user_id' => $user->id, // Associate the Income record with the created User
                'origin' => 'Registration Fee', // Mark the origin as Registration Fee
                'income_amount' => $userData['registration_fee'], // Set the income amount to registration_fee
            ]);
        }
        $this->call([
            AccountSeeder::class,
            MonthSeeder::class,
            YearSeeder::class,
        ]);
    }
}
