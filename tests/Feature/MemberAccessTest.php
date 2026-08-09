<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
use App\Enums\RoleEnum;
use App\Filament\Resources\AccountResource;
use App\Filament\Resources\DebtResource;
use App\Filament\Resources\IncomeResource;
use App\Filament\Resources\LoanResource;
use App\Filament\Resources\PayableResource;
use App\Filament\Resources\ReceivableResource;
use App\Filament\Resources\SavingResource;
use App\Filament\Resources\UserResource;
use App\Models\Account;
use App\Models\Receivable;
use App\Models\Saving;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Members must only ever see their own money.
 *
 * The role column existed from the start but was never enforced, so anyone who
 * could sign in saw the whole group's ledgers. These tests pin the boundary
 * down — including the specific way it broke while being built, where a
 * resource declaring its own getEloquentQuery() shadowed the trait that applies
 * the filter and quietly restored the leak.
 */
class MemberAccessTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        return User::factory()->create(['role' => RoleEnum::Member]);
    }

    private function treasurer(): User
    {
        return User::factory()->create(['role' => RoleEnum::Administrator]);
    }

    public function test_a_member_only_sees_their_own_collections(): void
    {
        $member = $this->member();
        $other = $this->member();
        $account = Account::factory()->create();

        foreach ([$member, $other] as $user) {
            Receivable::withoutEvents(fn () => Receivable::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'amount_contributed' => 1000,
            ]));
        }

        $this->actingAs($member);

        $visible = ReceivableResource::getEloquentQuery()->pluck('user_id')->unique();

        $this->assertEquals([$member->id], $visible->values()->all());
    }

    public function test_a_treasurer_sees_every_members_collections(): void
    {
        $treasurer = $this->treasurer();
        $other = $this->member();
        $account = Account::factory()->create();

        foreach ([$treasurer, $other] as $user) {
            Receivable::withoutEvents(fn () => Receivable::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'amount_contributed' => 1000,
            ]));
        }

        $this->actingAs($treasurer);

        $this->assertSame(2, ReceivableResource::getEloquentQuery()->count());
    }

    public function test_a_member_only_sees_their_own_savings_ledger(): void
    {
        $member = $this->member();
        $other = $this->member();

        foreach ([$member, $other] as $user) {
            Saving::create([
                'user_id' => $user->id,
                'credit_amount' => 500,
                'debit_amount' => 0,
                'balance' => 500,
                'net_worth' => 500,
            ]);
        }

        $this->actingAs($member);

        $this->assertEquals(
            [$member->id],
            SavingResource::getEloquentQuery()->pluck('user_id')->unique()->values()->all(),
        );
    }

    /**
     * Every resource that scopes to the signed-in member must apply the filter
     * even though several of them customise their own query for eager loading.
     */
    public function test_every_member_facing_resource_scopes_its_query(): void
    {
        $member = $this->member();
        $this->actingAs($member);

        $resources = [
            ReceivableResource::class,
            PayableResource::class,
            LoanResource::class,
            DebtResource::class,
            SavingResource::class,
        ];

        foreach ($resources as $resource) {
            $sql = $resource::getEloquentQuery()->toSql();

            $this->assertStringContainsString(
                'user_id',
                $sql,
                $resource.' does not scope its query to the signed-in member.',
            );
        }
    }

    public function test_members_cannot_reach_the_group_wide_screens(): void
    {
        $this->actingAs($this->member());

        $this->assertFalse(UserResource::canAccess());
        $this->assertFalse(AccountResource::canAccess());
        $this->assertFalse(IncomeResource::canAccess());
    }

    public function test_treasurers_can_reach_the_group_wide_screens(): void
    {
        $this->actingAs($this->treasurer());

        $this->assertTrue(UserResource::canAccess());
        $this->assertTrue(AccountResource::canAccess());
        $this->assertTrue(IncomeResource::canAccess());
    }

    public function test_members_cannot_record_money_movements(): void
    {
        $this->actingAs($this->member());

        $this->assertFalse(ReceivableResource::canCreate());
        $this->assertFalse(PayableResource::canCreate());
        $this->assertFalse(LoanResource::canCreate());
    }

    public function test_treasurers_can_record_money_movements(): void
    {
        $this->actingAs($this->treasurer());

        $this->assertTrue(ReceivableResource::canCreate());
        $this->assertTrue(PayableResource::canCreate());
        $this->assertTrue(LoanResource::canCreate());
    }

    /**
     * Debts are a consequence of a loan or of arrears, never something typed in
     * directly — so nobody, treasurer included, gets a "create" button.
     */
    public function test_nobody_creates_a_debt_by_hand(): void
    {
        $this->actingAs($this->treasurer());

        $this->assertFalse(DebtResource::canCreate());
        $this->assertFalse(SavingResource::canCreate());
    }

    /* ---------------------------------------------------------------------
     | Who may sign in at all
     |
     | The model did not answer this question, so Filament fell back to its own
     | default of "only when APP_ENV is local" — which let everyone in during
     | development and would have returned 403 on every page anywhere else.
     |---------------------------------------------------------------------*/

    public function test_an_active_member_can_sign_in(): void
    {
        $panel = \Filament\Facades\Filament::getPanel('app');

        $this->assertTrue($this->member()->canAccessPanel($panel));
        $this->assertTrue($this->treasurer()->canAccessPanel($panel));
    }

    /**
     * A member who has paused keeps their history and can still read it. The
     * app describes Inactive as "kept for historical records", so shutting them
     * out of their own statement would contradict what the status means. Their
     * role still limits them to their own money.
     */
    public function test_a_member_marked_inactive_can_still_read_their_own_history(): void
    {
        $paused = User::factory()->create([
            'role' => RoleEnum::Member,
            'member_status' => MemberStatus::Inactive,
        ]);

        $this->assertTrue($paused->canAccessPanel(\Filament\Facades\Filament::getPanel('app')));

        $this->actingAs($paused);
        $this->assertFalse(UserResource::canAccess());
    }
}
