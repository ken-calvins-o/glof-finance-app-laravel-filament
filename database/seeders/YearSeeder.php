<?php

namespace Database\Seeders;

use App\Models\Year; // Import the Year model
use Illuminate\Database\Seeder;

class YearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the years starting from 2024
        $years = range(2024, 2034); // This will generate an array from 2024 to 2034

        // Insert the years using Eloquent
        foreach ($years as $year) {
            Year::create(['year' => $year]);
        }
    }
}
