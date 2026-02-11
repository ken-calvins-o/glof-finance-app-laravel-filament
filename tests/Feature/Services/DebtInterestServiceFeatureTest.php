<?php

namespace Tests\Feature\Services;

use App\Models\Debt;
use App\Models\User;
use App\Models\Account;
use App\Services\DebtInterestService;
use Tests\TestCase;

/**
 * Integration tests for DebtInterestService
 *
 * Tests the service with real database interactions to ensure
 * the service correctly persists interest changes to the database.
 * These tests use real seeded data and are slower than unit tests.
 */
class DebtInterestServiceFeatureTest extends TestCase
{
    private DebtInterestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Clean up test debts before each test while preserving seeded data
        Debt::truncate();
        $this->service = new DebtInterestService();
    }

    /**
     * Test applying interest to a single debt using real database
     */
    public function test_applies_one_percent_interest_correctly_with_database(): void
    {
        $user = User::first();
        $account = Account::first();

        $debt = Debt::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'outstanding_balance' => 1000.00,
        ]);

        $stats = $this->service->applyMonthlyInterest();

        $this->assertEquals(1, $stats['processed']);
        $this->assertEquals(0, $stats['errors']);
        $this->assertEquals(10.00, $stats['total_interest']);

        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'outstanding_balance' => 1010.00,
        ]);
    }

    /**
     * Test skipping debts with zero or negative balance
     */
    public function test_skips_debts_with_zero_or_negative_balance(): void
    {
        $user = User::first();
        $account = Account::first();

        Debt::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'outstanding_balance' => 0.00,
        ]);

        Debt::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'outstanding_balance' => -100.00,
        ]);

        $stats = $this->service->applyMonthlyInterest();

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(0, $stats['errors']);
    }

    /**
     * Test processing multiple debts
     */
    public function test_processes_multiple_debts(): void
    {
        $users = User::limit(2)->get();
        $account = Account::first();

        if ($users->count() < 2) {
            $this->markTestSkipped('At least 2 users required in database for this test');
            return;
        }

        $user1 = $users[0];
        $user2 = $users[1];

        $debt1 = Debt::factory()->create([
            'user_id' => $user1->id,
            'account_id' => $account->id,
            'outstanding_balance' => 500.00,
        ]);

        $debt2 = Debt::factory()->create([
            'user_id' => $user2->id,
            'account_id' => $account->id,
            'outstanding_balance' => 2000.00,
        ]);

        $stats = $this->service->applyMonthlyInterest();

        $this->assertEquals(2, $stats['processed']);
        $this->assertEquals(0, $stats['errors']);
        $this->assertEquals(25.00, $stats['total_interest']); // 5.00 + 20.00

        $this->assertDatabaseHas('debts', [
            'id' => $debt1->id,
            'outstanding_balance' => 505.00,
        ]);

        $this->assertDatabaseHas('debts', [
            'id' => $debt2->id,
            'outstanding_balance' => 2020.00,
        ]);
    }

    /**
     * Test custom interest rate
     */
    public function test_applies_custom_interest_rate(): void
    {
        $user = User::first();
        $account = Account::first();

        Debt::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'outstanding_balance' => 1000.00,
        ]);

        $this->service->setInterestRate(0.05); // 5% interest
        $stats = $this->service->applyMonthlyInterest();

        $this->assertEquals(1, $stats['processed']);
        $this->assertEquals(50.00, $stats['total_interest']);
    }

    /**
     * Test decimal precision
     */
    public function test_maintains_decimal_precision(): void
    {
        $user = User::first();
        $account = Account::first();

        Debt::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'outstanding_balance' => 333.33,
        ]);

        $stats = $this->service->applyMonthlyInterest();

        // 333.33 * 0.01 = 3.3333, which should round to 3.33
        $this->assertDatabaseHas('debts', [
            'outstanding_balance' => 336.66, // 333.33 + 3.33
        ]);

        $this->assertEquals(3.33, $stats['total_interest']);
    }
}

