<?php

namespace Database\Seeders;

use App\Models\Month; // Import the Month model
use Illuminate\Database\Seeder;

class MonthSeeder extends Seeder
{
    public function run()
    {
        // Define the month names only
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        // Insert the months using Eloquent
        foreach ($months as $month) {
            Month::create(['name' => $month]);
        }
    }
}
