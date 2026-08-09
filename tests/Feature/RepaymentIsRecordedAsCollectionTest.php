<?php

namespace Tests\Feature;

use App\Enums\DebtStatusEnum;
use App\Models\Account;
use App\Models\AccountCollection;
use App\Models\Debt;
use App\Models\Receivable;
use App\Models\Saving;
use App\Models\User;
use App\Services\DebtRepaymentService;
use App\Services\GroupMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Settling a debt is money coming in, so it has to appear where money in
 * appears.
 *
 * The reported case: a member paid 4,000 into a fund, was charged 5,000 as a
 * payment out, and so owed 1,000. When they settled that 1,000 the app moved
 * the money correctly — the debt cleared, the fund total corrected, the savings
 * ledger updated — but wrote no Receivable. Since Receivable is what the
 * Collections screen, a member's recent activity and the dashboard's "collected
 * this month" all read, the repayment showed up on none of them and the total
 * collected stayed at 4,000 instead of 5,000.
 */
class RepaymentIsRecordedAsCollectionTest extends TestCase
{
    use RefreshDatabase;

    private function memberOwing(float $owed, ?Account $fund = null): array
    {
        $user = User::factory()->create();
        $fund ??= Account::factory()->create();

        $debt = Debt::create([
            'user_id' => $user->id,
            'account_id' => $fund->id,
            'outstanding_balance' => $owed,
            'debt_status' => DebtStatusEnum::Pending,
        ]);

        return [$user, $fund, $debt];
    }

    public function test_a_repayment_appears_as_a_collection(): void
    {
        [$user, $fund, $debt] = $this->memberOwing(1000);

        $this->assertSame(0, Receivable::where('user_id', $user->id)->count());

        app(DebtRepaymentService::class)->apply($debt, 1000);

        $collection = Receivable::where('user_id', $user->id)->sole();

        $this->assertEquals(1000, (float) $collection->amount_contributed);
        $this->assertSame($fund->id, $collection->account_id);
        $this->assertTrue($collection->isDebtRepayment());
    }

    public function test_it_counts_towards_the_money_collected(): void
    {
        [$user, $fund, $debt] = $this->memberOwing(1000);

        $before = app(GroupMetrics::class)->collectedThisMonth();

        app(DebtRepaymentService::class)->apply($debt, 1000);

        $this->assertEqualsWithDelta(
            $before + 1000,
            app(GroupMetrics::class)->collectedThisMonth(),
            0.001,
        );
    }

    public function test_it_shows_in_the_members_recent_activity(): void
    {
        [$user, $fund, $debt] = $this->memberOwing(1000);

        app(DebtRepaymentService::class)->apply($debt, 1000);

        $this->assertSame(1, $user->receivables()->count());
    }

    /**
     * The whole reported sequence, end to end.
     */
    public function test_the_reported_scenario_totals_five_thousand(): void
    {
        $user = User::factory()->create();
        $fund = Account::factory()->create(['name' => 'Bereavement']);

        // 1. A collection of 4,000.
        Receivable::withoutEvents(fn () => Receivable::create([
            'user_id' => $user->id,
            'account_id' => $fund->id,
            'amount_contributed' => 4000,
        ]));
        AccountCollection::create(['user_id' => $user->id, 'account_id' => $fund->id, 'amount' => 4000]);

        // 2. Charged 5,000, leaving 1,000 owed.
        $debt = Debt::create([
            'user_id' => $user->id,
            'account_id' => $fund->id,
            'outstanding_balance' => 1000,
            'debt_status' => DebtStatusEnum::Pending,
        ]);
        AccountCollection::where('user_id', $user->id)->update(['amount' => -1000]);

        // 3. The member settles the 1,000.
        app(DebtRepaymentService::class)->apply($debt, 1000);

        $this->assertEqualsWithDelta(5000, app(GroupMetrics::class)->collectedThisMonth(), 0.001);
        $this->assertSame(2, $user->receivables()->count());
        $this->assertEqualsWithDelta(
            0,
            (float) AccountCollection::where('user_id', $user->id)->value('amount'),
            0.001,
            'Paid in 5,000 and charged 5,000, so the fund should net to zero.',
        );
    }

    /**
     * Paying out of savings moves money the group already holds. Nothing new
     * arrives, so counting it as a collection would inflate the totals.
     */
    public function test_repaying_from_savings_is_not_counted_as_a_collection(): void
    {
        [$user, $fund, $debt] = $this->memberOwing(1000);

        Saving::create([
            'user_id' => $user->id,
            'credit_amount' => 5000,
            'debit_amount' => 0,
            'balance' => 5000,
            'net_worth' => 5000,
        ]);

        app(DebtRepaymentService::class)->apply($debt, 1000, fromSavings: true);

        $this->assertSame(0, Receivable::where('user_id', $user->id)->count());
    }

    public function test_a_part_payment_records_only_what_was_paid(): void
    {
        [$user, $fund, $debt] = $this->memberOwing(1000);

        app(DebtRepaymentService::class)->apply($debt, 400);

        $this->assertEquals(400, (float) Receivable::where('user_id', $user->id)->sole()->amount_contributed);
        $this->assertEquals(600, (float) $debt->refresh()->outstanding_balance);
    }

    /**
     * The collection row must not double-count into the fund: apply() credits
     * the fund itself, so recording the receivable must leave that alone.
     */
    public function test_the_fund_total_moves_by_the_repayment_only_once(): void
    {
        [$user, $fund, $debt] = $this->memberOwing(1000);

        AccountCollection::create(['user_id' => $user->id, 'account_id' => $fund->id, 'amount' => 0]);

        app(DebtRepaymentService::class)->apply($debt, 1000);

        $this->assertEqualsWithDelta(
            1000,
            (float) AccountCollection::where('user_id', $user->id)->value('amount'),
            0.001,
        );
    }

    /**
     * The repayment row carries no reversal snapshot, because the service
     * already applied the debt, fund and savings changes itself — reversing it
     * would undo the collection without restoring the debt.
     */
    public function test_a_repayment_collection_records_no_reversal_effects(): void
    {
        [$user, $fund, $debt] = $this->memberOwing(1000);

        app(DebtRepaymentService::class)->apply($debt, 1000);

        $collection = Receivable::where('user_id', $user->id)->sole();

        $this->assertSame(0, $collection->effects()->count());
    }

    public function test_an_ordinary_collection_is_not_marked_as_a_repayment(): void
    {
        $user = User::factory()->create();
        $fund = Account::factory()->create();

        $collection = Receivable::withoutEvents(fn () => Receivable::create([
            'user_id' => $user->id,
            'account_id' => $fund->id,
            'amount_contributed' => 2500,
        ]));

        $this->assertFalse($collection->isDebtRepayment());
        $this->assertNull($collection->source);
    }
}
