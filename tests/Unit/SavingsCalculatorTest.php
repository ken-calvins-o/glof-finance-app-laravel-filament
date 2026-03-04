<?php

namespace Tests\Unit;

use App\Models\Saving;
use App\Models\User;
use App\Services\SavingsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected SavingsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SavingsCalculator();
    }

    /** @test */
    public function it_calculates_new_net_worth_correctly()
    {
        $currentNetWorth = 1000.50;
        $creditAmount = 250.25;

        $result = $this->calculator->calculateNewNetWorth($currentNetWorth, $creditAmount);

        $this->assertEquals(1250.75, $result);
    }

    /** @test */
    public function it_calculates_balance_correctly()
    {
        $currentBalance = 2000;
        $creditAmount = 3000;

        $result = $this->calculator->calculateBalance($currentBalance, $creditAmount);

        $this->assertEquals(5000, $result);
    }

    /** @test */
    public function it_calculates_balance_with_debit_correctly()
    {
        $currentBalance = 5000;
        $creditAmount = 0;
        $debitAmount = 1000;

        $result = $this->calculator->calculateBalance($currentBalance, $creditAmount, $debitAmount);

        $this->assertEquals(4000, $result);
    }

    /** @test */
    public function it_returns_zero_net_worth_when_user_has_no_savings()
    {
        $user = User::factory()->create();

        $result = $this->calculator->getCurrentNetWorth($user->id);

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_returns_latest_net_worth_for_user()
    {
        $user = User::factory()->create();

        // Create multiple savings records
        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 100,
            'debit_amount' => 0,
            'balance' => 100,
            'net_worth' => 100,
        ]);

        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 200,
            'debit_amount' => 0,
            'balance' => 200,
            'net_worth' => 300,
        ]);

        $result = $this->calculator->getCurrentNetWorth($user->id);

        $this->assertEquals(300, $result);
    }

    /** @test */
    public function it_returns_latest_balance_for_user()
    {
        $user = User::factory()->create();

        // Create multiple savings records
        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 100,
            'debit_amount' => 0,
            'balance' => 100,
            'net_worth' => 100,
        ]);

        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 200,
            'debit_amount' => 0,
            'balance' => 300,
            'net_worth' => 300,
        ]);

        $result = $this->calculator->getCurrentBalance($user->id);

        $this->assertEquals(300, $result);
    }

    /** @test */
    public function it_returns_correct_form_defaults()
    {
        $user = User::factory()->create();

        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 500,
            'debit_amount' => 0,
            'balance' => 500,
            'net_worth' => 500,
        ]);

        $creditAmount = 250;
        $result = $this->calculator->getFormDefaults($user->id, $creditAmount);

        $this->assertIsArray($result);
        $this->assertEquals(500, $result['current_net_worth']);
        $this->assertEquals(500, $result['current_balance']);
        $this->assertEquals(750, $result['net_worth']);
        $this->assertEquals(750, $result['balance']); // 500 + 250
    }

    /** @test */
    public function it_returns_correct_reset_values()
    {
        $result = $this->calculator->getResetValues();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['current_net_worth']);
        $this->assertEquals(0, $result['current_balance']);
        $this->assertEquals(0, $result['net_worth']);
        $this->assertEquals(0, $result['balance']);
    }
}





