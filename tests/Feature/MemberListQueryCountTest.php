<?php

namespace Tests\Feature;

use App\Enums\DebtStatusEnum;
use App\Models\Debt;
use App\Models\Saving;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Listing members must not cost a query per row.
 *
 * The members table shows three figures that are not columns on the table —
 * savings balance, net worth and money owed — and each was originally worked
 * out by its own accessor, per record. Drawing a roll of 32 members therefore
 * fired over a hundred queries. They are now selected as subqueries alongside
 * the members themselves.
 *
 * This test pins the shape rather than a stopwatch: the query count must not
 * grow with the number of members.
 */
class MemberListQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private function makeMembers(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create();

            Saving::create([
                'user_id' => $user->id,
                'credit_amount' => 1000,
                'debit_amount' => 0,
                'balance' => 1000 + $i,
                'net_worth' => 2000 + $i,
            ]);

            Debt::create([
                'user_id' => $user->id,
                'outstanding_balance' => 500,
                'debt_status' => DebtStatusEnum::Pending,
            ]);
        }
    }

    private function countQueriesListing(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        User::query()->withMoneyTotals()->get()->each(function (User $user) {
            // Touch every figure the table renders.
            $user->savings_balance;
            $user->net_worth;
            $user->outstanding_debt;
        });

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_the_query_count_does_not_grow_with_the_number_of_members(): void
    {
        $this->makeMembers(3);
        $few = $this->countQueriesListing();

        $this->makeMembers(20);
        $many = $this->countQueriesListing();

        $this->assertSame(
            $few,
            $many,
            "Listing members costs more queries as the group grows ({$few} for 3, {$many} for 23) — the per-row accessors are being hit again.",
        );
    }

    public function test_the_whole_list_is_fetched_in_a_single_query(): void
    {
        $this->makeMembers(10);

        $this->assertSame(1, $this->countQueriesListing());
    }

    public function test_the_figures_are_the_same_either_way(): void
    {
        $this->makeMembers(3);

        foreach (User::query()->withMoneyTotals()->get() as $listed) {
            // A member loaded on their own falls back to the per-record
            // accessors, which must agree with the subquery values.
            $alone = User::query()->whereKey($listed->getKey())->first();

            $this->assertEqualsWithDelta($alone->savings_balance, $listed->savings_balance, 0.001);
            $this->assertEqualsWithDelta($alone->net_worth, $listed->net_worth, 0.001);
            $this->assertEqualsWithDelta($alone->outstanding_debt, $listed->outstanding_debt, 0.001);
        }
    }

    public function test_a_member_with_no_history_reads_as_zero(): void
    {
        $user = User::factory()->create();

        $listed = User::query()->withMoneyTotals()->whereKey($user->getKey())->first();

        $this->assertSame(0.0, $listed->savings_balance);
        $this->assertSame(0.0, $listed->net_worth);
        $this->assertSame(0.0, $listed->outstanding_debt);
    }

    public function test_settled_debts_are_not_counted_as_owing(): void
    {
        $user = User::factory()->create();

        Debt::create([
            'user_id' => $user->id,
            'outstanding_balance' => 900,
            'debt_status' => DebtStatusEnum::Cleared,
        ]);

        $listed = User::query()->withMoneyTotals()->whereKey($user->getKey())->first();

        $this->assertSame(0.0, $listed->outstanding_debt);
    }
}
