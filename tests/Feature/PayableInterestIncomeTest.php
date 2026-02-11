<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\AccountCollection;
use App\Models\Payable;
use App\Models\Income;
use App\Models\Debt;
use App\Models\Saving;
use App\Models\Month;
use App\Models\Year;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayableInterestIncomeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that interest is recorded as income when a user incurs debt from payable.
     *
     * @return void
     */
    public function test_interest_is_recorded_as_income_when_debt_is_incurred()
    {
        // Arrange: Create test data
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $month = Month::factory()->create();
        $year = Year::factory()->create();

        // Set account collection to 500 (less than payable amount)
        AccountCollection::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 500,
        ]);

        // Create initial saving record
        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 0,
            'debit_amount' => 0,
            'balance' => 0,
            'net_worth' => 1000,
        ]);

        // Payable amount is 1000, user has 500, shortfall is 500
        // Interest should be 500 * 0.01 = 5
        $payableAmount = 1000;

        // Act: Create payable (this should trigger the interest income recording)
        $response = $this->actingAs($user)->post(route('filament.admin.resources.payables.create'), [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'total_amount' => $payableAmount,
            'is_general' => false,
            'from_savings' => false,
            'month_id' => $month->id,
            'year_id' => $year->id,
            'users' => [
                [
                    'user_id' => $user->id,
                    'total_amount' => $payableAmount,
                    'from_savings' => false,
                ],
            ],
        ]);

        // Assert: Verify income record was created
        $this->assertDatabaseHas('incomes', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'origin' => 'Payable Interest',
            'interest_amount' => 5.00,
            'income_amount' => 0,
        ]);

        // Assert: Verify debt was created with correct outstanding balance
        // Outstanding balance = (1000 + 5) - 500 = 505
        $this->assertDatabaseHas('debts', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'outstanding_balance' => 505.00,
        ]);
    }

    /**
     * Test that no income record is created when no debt is incurred.
     *
     * @return void
     */
    public function test_no_income_record_when_no_debt_incurred()
    {
        // Arrange: Create test data
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $month = Month::factory()->create();
        $year = Year::factory()->create();

        // Set account collection to 2000 (more than payable amount)
        AccountCollection::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 2000,
        ]);

        // Create initial saving record
        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 0,
            'debit_amount' => 0,
            'balance' => 0,
            'net_worth' => 2000,
        ]);

        $payableAmount = 1000;
        $initialIncomeCount = Income::count();

        // Act: Create payable (no debt should be incurred)
        $response = $this->actingAs($user)->post(route('filament.admin.resources.payables.create'), [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'total_amount' => $payableAmount,
            'is_general' => false,
            'from_savings' => false,
            'month_id' => $month->id,
            'year_id' => $year->id,
            'users' => [
                [
                    'user_id' => $user->id,
                    'total_amount' => $payableAmount,
                    'from_savings' => false,
                ],
            ],
        ]);

        // Assert: Verify no new income record was created
        $this->assertEquals($initialIncomeCount, Income::count());

        // Assert: Verify no debt was created
        $this->assertDatabaseMissing('debts', [
            'user_id' => $user->id,
            'account_id' => $account->id,
        ]);
    }

    /**
     * Test interest calculation with various shortfall amounts.
     *
     * @return void
     */
    public function test_interest_calculation_with_various_amounts()
    {
        $testCases = [
            ['available' => 0, 'payable' => 1000, 'expected_interest' => 10.00],
            ['available' => 250, 'payable' => 1000, 'expected_interest' => 7.50],
            ['available' => 500, 'payable' => 1000, 'expected_interest' => 5.00],
            ['available' => 900, 'payable' => 1000, 'expected_interest' => 1.00],
            ['available' => 999, 'payable' => 1000, 'expected_interest' => 0.01],
        ];

        foreach ($testCases as $index => $case) {
            // Arrange
            $user = User::factory()->create(['name' => "Test User {$index}"]);
            $account = Account::factory()->create(['name' => "Test Account {$index}"]);
            $month = Month::factory()->create();
            $year = Year::factory()->create();

            AccountCollection::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'amount' => $case['available'],
            ]);

            Saving::create([
                'user_id' => $user->id,
                'credit_amount' => 0,
                'debit_amount' => 0,
                'balance' => 0,
                'net_worth' => 1000,
            ]);

            // Act
            $response = $this->actingAs($user)->post(route('filament.admin.resources.payables.create'), [
                'account_id' => $account->id,
                'user_id' => $user->id,
                'total_amount' => $case['payable'],
                'is_general' => false,
                'from_savings' => false,
                'month_id' => $month->id,
                'year_id' => $year->id,
                'users' => [
                    [
                        'user_id' => $user->id,
                        'total_amount' => $case['payable'],
                        'from_savings' => false,
                    ],
                ],
            ]);

            // Assert
            $income = Income::where('user_id', $user->id)
                ->where('account_id', $account->id)
                ->where('origin', 'Payable Interest')
                ->first();

            $this->assertNotNull($income, "Income record should exist for test case {$index}");
            $this->assertEquals(
                $case['expected_interest'],
                (float) $income->interest_amount,
                "Interest amount should be {$case['expected_interest']} for test case {$index}"
            );
        }
    }
}

