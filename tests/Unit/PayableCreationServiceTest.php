<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\AccountCollection;
use App\Models\Debt;
use App\Models\Month;
use App\Models\Saving;
use App\Models\User;
use App\Models\Year;
use App\Services\PayableCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayableCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_payable_from_savings_updates_balance_but_not_net_worth(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $month = Month::create(['name' => 'January']);
        $year = Year::create(['year' => 2026]);

        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 0,
            'debit_amount' => 0,
            'balance' => 500,
            'net_worth' => 1000,
        ]);

        $data = [
            'account_id' => $account->id,
            'month_id' => $month->id,
            'year_id' => $year->id,
            'is_general' => false,
            'users' => [
                ['user_id' => $user->id, 'total_amount' => 200, 'from_savings' => true],
            ],
        ];

        $payables = app(PayableCreationService::class)->create($data);

        $this->assertCount(1, $payables);
        $this->assertDatabaseHas('payables', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'total_amount' => 200,
            'from_savings' => 1,
        ]);

        $latestSaving = Saving::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($latestSaving);
        $this->assertEquals(300.0, (float) $latestSaving->balance);
        $this->assertEquals(1000.0, (float) $latestSaving->net_worth);

        $this->assertDatabaseMissing('debts', [
            'user_id' => $user->id,
            'account_id' => $account->id,
        ]);
    }

    public function test_custom_payable_not_from_savings_creates_debt_for_shortfall(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $month = Month::create(['name' => 'January']);
        $year = Year::create(['year' => 2026]);

        AccountCollection::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 250,
        ]);

        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 0,
            'debit_amount' => 0,
            'balance' => 0,
            'net_worth' => 1000,
        ]);

        $data = [
            'account_id' => $account->id,
            'month_id' => $month->id,
            'year_id' => $year->id,
            'is_general' => false,
            'users' => [
                ['user_id' => $user->id, 'total_amount' => 600, 'from_savings' => false],
            ],
        ];

        app(PayableCreationService::class)->create($data);

        $debt = Debt::where('user_id', $user->id)->where('account_id', $account->id)->first();
        $this->assertNotNull($debt);
        $this->assertEquals(350.0, (float) $debt->outstanding_balance);

        $ac = AccountCollection::where('user_id', $user->id)->where('account_id', $account->id)->first();
        $this->assertNotNull($ac);
        $this->assertEquals(-350.0, (float) $ac->amount);
    }
}

