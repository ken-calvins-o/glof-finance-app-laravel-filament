<?php

namespace Tests\Unit;

use App\Enums\DebtStatusEnum;
use App\Models\Account;
use App\Models\AccountCollection;
use App\Models\Debt;
use App\Models\Saving;
use App\Models\User;
use App\Services\DebtRepaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Covers the repayment logic that was lifted out of the debt edit page so it
 * could also be run from the debts table and the dashboard.
 */
class DebtRepaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function debtFor(User $user, Account $account, float $outstanding): Debt
    {
        return Debt::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'outstanding_balance' => $outstanding,
            'debt_status' => DebtStatusEnum::Pending,
        ]);
    }

    public function test_a_part_payment_reduces_the_balance_and_marks_it_partly_repaid(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $debt = $this->debtFor($user, $account, 1000);

        $updated = app(DebtRepaymentService::class)->apply($debt, 400);

        $this->assertEquals(600, (float) $updated->outstanding_balance);
        $this->assertSame(DebtStatusEnum::Partially_Paid, $updated->debt_status);
    }

    public function test_paying_the_full_balance_clears_the_debt(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $debt = $this->debtFor($user, $account, 1000);

        $updated = app(DebtRepaymentService::class)->apply($debt, 1000);

        $this->assertEquals(0, (float) $updated->outstanding_balance);
        $this->assertSame(DebtStatusEnum::Cleared, $updated->debt_status);
    }

    public function test_repaying_more_than_is_owed_is_refused(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $debt = $this->debtFor($user, $account, 1000);

        $this->expectException(RuntimeException::class);

        app(DebtRepaymentService::class)->apply($debt, 1500);
    }

    public function test_repaying_from_savings_the_member_does_not_have_is_refused(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $debt = $this->debtFor($user, $account, 1000);

        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 100,
            'debit_amount' => 0,
            'balance' => 100,
            'net_worth' => 100,
        ]);

        $this->expectException(RuntimeException::class);

        app(DebtRepaymentService::class)->apply($debt, 500, fromSavings: true);
    }

    public function test_a_repayment_is_credited_back_to_the_fund_it_was_owed_against(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $debt = $this->debtFor($user, $account, 1000);

        AccountCollection::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 2000,
        ]);

        app(DebtRepaymentService::class)->apply($debt, 400);

        $this->assertEquals(2400, (float) AccountCollection::where('user_id', $user->id)
            ->where('account_id', $account->id)
            ->value('amount'));
    }

    public function test_a_fresh_payment_is_recorded_as_a_credit_on_the_savings_ledger(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $debt = $this->debtFor($user, $account, 1000);

        app(DebtRepaymentService::class)->apply($debt, 400);

        $saving = Saving::where('user_id', $user->id)->latest('id')->first();

        $this->assertEquals(400, (float) $saving->credit_amount);
        $this->assertEquals(0, (float) $saving->debit_amount);
    }

    public function test_paying_from_savings_is_recorded_as_a_debit_and_lowers_the_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $debt = $this->debtFor($user, $account, 1000);

        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 5000,
            'debit_amount' => 0,
            'balance' => 5000,
            'net_worth' => 5000,
        ]);

        app(DebtRepaymentService::class)->apply($debt, 400, fromSavings: true);

        $saving = Saving::where('user_id', $user->id)->latest('id')->first();

        $this->assertEquals(400, (float) $saving->debit_amount);
        $this->assertEquals(4600, (float) $saving->balance);
    }

    public function test_a_zero_repayment_is_refused(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $debt = $this->debtFor($user, $account, 1000);

        $this->expectException(RuntimeException::class);

        app(DebtRepaymentService::class)->apply($debt, 0);
    }
}
