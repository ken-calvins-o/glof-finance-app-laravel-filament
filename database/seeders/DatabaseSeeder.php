<?php

namespace Database\Seeders;

use App\Enums\PaymentMode;
use App\Enums\RoleEnum;
use App\Models\Saving;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultPassword = bcrypt('password');

        // Seed users and savings
        $users = [
            [
                'name' => 'Kennedy Calvins',
                'email' => 'ken.calvins.o@gmail.com',
                'registration_fee' => 10000,
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Jane Doe',
                'email' => 'jane.doe@example.com',
                'registration_fee' => 10000,
                'password' => $defaultPassword,
                'role' => RoleEnum::Member
            ],
            [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'registration_fee' => 10000,
                'password' => $defaultPassword,
                'role' => RoleEnum::Member
            ],
            [
                'name' => 'Alice Johnson',
                'email' => 'alice.johnson@example.com',
                'registration_fee' => 10000,
                'password' => $defaultPassword,
                'role' => RoleEnum::Member
            ],
        ];

        foreach ($users as $userData) {
            $user = User::factory()->create($userData);

            // Generate a fixed amount and use it for both amount and net_worth
            $fixedAmount = rand(5000, 20000); // Random fixed amount between 5000 and 20000

            Saving::create([
                'user_id' => $user->id,
                'credit_amount' => $fixedAmount,
                'balance' => $fixedAmount, // Same as credit_amount
                'net_worth' => $fixedAmount, // Same as credit_amount
                'payment_method' => PaymentMode::Mobile_Money,
            ]);
        }
    }
}
