<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\AccountCollection;
use App\Models\Income;
use App\Models\Saving;
use App\Models\Month;
use App\Models\Year;
use App\Services\PayableCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayableInterestIncomeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that no payable-interest income is recorded, and debt is principal shortfall only.
     *
     * @return void
     */
    public function test_debt_is_created_without_interest_when_user_incurrs_shortfall()
    {
        // Arrange
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $month = Month::create(['name' => 'January']);
        $year = Year::create(['year' => 2026]);

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
        $payableAmount = 1000;
        $initialIncomeCount = Income::count();

        // Act
        app(PayableCreationService::class)->create([
            'account_id' => $account->id,
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

        // Assert: no new income record created for payable interest
        $this->assertEquals($initialIncomeCount, Income::count());
        $this->assertDatabaseMissing('incomes', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'origin' => 'Payable Interest',
        ]);

        // Assert: debt is principal shortfall only (1000 - 500 = 500)
        $this->assertDatabaseHas('debts', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'outstanding_balance' => 500.00,
        ]);
    }

    /**
     * Test that no income record is created when no debt is incurred.
     *
     * @return void
     */
    public function test_no_income_record_when_no_debt_incurred()
    {
        // Arrange
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $month = Month::create(['name' => 'January']);
        $year = Year::create(['year' => 2026]);

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

        // Act
        app(PayableCreationService::class)->create([
            'account_id' => $account->id,
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
     * Test shortfall calculation without interest across various scenarios.
     *
     * @return void
     */
    public function test_shortfall_to_debt_calculation_with_various_amounts()
    {
        $testCases = [
            ['available' => 0, 'payable' => 1000, 'expected_debt' => 1000.00],
            ['available' => 250, 'payable' => 1000, 'expected_debt' => 750.00],
            ['available' => 500, 'payable' => 1000, 'expected_debt' => 500.00],
            ['available' => 900, 'payable' => 1000, 'expected_debt' => 100.00],
            ['available' => 999, 'payable' => 1000, 'expected_debt' => 1.00],
            ['available' => 1000, 'payable' => 1000, 'expected_debt' => 0.00],
            ['available' => 1500, 'payable' => 1000, 'expected_debt' => 0.00],
        ];

        foreach ($testCases as $index => $case) {
            // Arrange
            $user = User::factory()->create(['name' => "Test User {$index}"]);
            $account = Account::factory()->create(['name' => "Test Account {$index}"]);
            $month = Month::create(['name' => 'January']);
            $year = Year::create(['year' => 2026]);

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
                'net_worth' => 2000,
            ]);

            $initialIncomeCount = Income::count();

            // Act
            app(PayableCreationService::class)->create([
                'account_id' => $account->id,
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

            // Assert: no payable interest income created
            $this->assertEquals($initialIncomeCount, Income::count(), "No income should be created for test case {$index}");

            if ($case['expected_debt'] > 0) {
                $this->assertDatabaseHas('debts', [
                    'user_id' => $user->id,
                    'account_id' => $account->id,
                    'outstanding_balance' => $case['expected_debt'],
                ]);
            } else {
                $this->assertDatabaseMissing('debts', [
                    'user_id' => $user->id,
                    'account_id' => $account->id,
                ]);
            }
        }
    }
}

