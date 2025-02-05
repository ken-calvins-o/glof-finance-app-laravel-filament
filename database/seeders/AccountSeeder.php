<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            'Administration', 'Bereavement', 'Insurance', 'Miscellaneous', 'Party/Rural Visit'
        ];

        foreach ($accounts as $account) {
            Account::create(['name' => $account]);
        }
    }
}
