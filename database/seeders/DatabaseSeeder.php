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
                'name' => 'John Owegi',
                'email' => 'admin@glof.co.ke',
                'registration_fee' => 1000,
                'password' => $defaultPassword,
                'role' => RoleEnum::Administrator
            ],
        ];

        foreach ($users as $userData) {
            $user = User::factory()->create($userData);

            // Generate a fixed amount and use it for both amount and net_worth
            $fixedAmount = 1000; // Random fixed amount between 5000 and 20000

            Saving::create([
                'user_id' => $user->id,
                'credit_amount' => $fixedAmount,
                'balance' => $fixedAmount, // Same as credit_amount
                'net_worth' => $fixedAmount, // Same as credit_amount
                'payment_method' => PaymentMode::Mobile_Money,
            ]);
        }
        $this->call([
            AccountSeeder::class,
            MonthSeeder::class,
            YearSeeder::class,
        ]);
    }
}
