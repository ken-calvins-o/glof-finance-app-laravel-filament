<?php

namespace Database\Factories;

use App\Enums\DebtStatusEnum;
use App\Models\Debt;
use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class DebtFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Debt::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'outstanding_balance' => $this->faker->randomFloat(2, 100, 10000),
            'repayment_amount' => $this->faker->randomFloat(2, 10, 1000),
            'from_savings' => $this->faker->boolean(),
            'debt_status' => DebtStatusEnum::Pending,
        ];
    }
}

