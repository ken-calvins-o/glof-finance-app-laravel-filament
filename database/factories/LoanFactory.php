<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Loan;
use App\Models\User;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'description' => $this->faker->text(),
            'amount' => $this->faker->randomFloat(2, 0, 99999999.99),
            'balance' => $this->faker->word(),
            'interest' => $this->faker->word(),
            'due_date' => $this->faker->dateTime(),
            'user_id' => User::factory(),
        ];
    }
}
