<?php

namespace Tests\Feature;

use App\Filament\Resources\ReceivableResource\Pages\CreateReceivable;
use App\Models\Account;
use App\Models\AccountCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A member's first contribution to a fund.
 *
 * This path was broken on every database. The running total was written with
 * updateOrCreate() and a raw `COALESCE(amount, 0) + x` expression, which is
 * valid in an UPDATE but not in the INSERT that updateOrCreate falls through to
 * when no row exists yet. So the first contribution to a fund always threw, and
 * only subsequent ones worked.
 */
class FirstContributionToAFundTest extends TestCase
{
    use RefreshDatabase;

    private function addContribution(User $user, Account $account, float $amount): void
    {
        $method = new ReflectionMethod(CreateReceivable::class, 'updateOrCreateAccountCollection');
        $method->setAccessible(true);
        $method->invoke(new CreateReceivable(), $user->id, $account->id, $amount);
    }

    public function test_the_first_contribution_to_a_fund_creates_the_running_total(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();

        $this->assertSame(0, AccountCollection::count());

        $this->addContribution($user, $account, 2500);

        $this->assertEquals(2500, (float) AccountCollection::where('user_id', $user->id)
            ->where('account_id', $account->id)
            ->value('amount'));
    }

    public function test_later_contributions_add_to_the_running_total(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();

        $this->addContribution($user, $account, 2500);
        $this->addContribution($user, $account, 1500);
        $this->addContribution($user, $account, 1000);

        $this->assertEquals(5000, (float) AccountCollection::where('user_id', $user->id)
            ->where('account_id', $account->id)
            ->value('amount'));

        // One running total per member per fund, not one row per contribution.
        $this->assertSame(1, AccountCollection::count());
    }

    public function test_arrears_reduce_the_running_total(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();

        $this->addContribution($user, $account, 2000);
        $this->addContribution($user, $account, -500);

        $this->assertEquals(1500, (float) AccountCollection::where('user_id', $user->id)
            ->where('account_id', $account->id)
            ->value('amount'));
    }

    public function test_each_member_gets_their_own_running_total_per_fund(): void
    {
        $one = User::factory()->create();
        $two = User::factory()->create();
        $bereavement = Account::factory()->create();
        $insurance = Account::factory()->create();

        $this->addContribution($one, $bereavement, 1000);
        $this->addContribution($one, $insurance, 2000);
        $this->addContribution($two, $bereavement, 3000);

        $this->assertSame(3, AccountCollection::count());
        $this->assertEquals(1000, (float) AccountCollection::where('user_id', $one->id)->where('account_id', $bereavement->id)->value('amount'));
        $this->assertEquals(2000, (float) AccountCollection::where('user_id', $one->id)->where('account_id', $insurance->id)->value('amount'));
        $this->assertEquals(3000, (float) AccountCollection::where('user_id', $two->id)->where('account_id', $bereavement->id)->value('amount'));
    }
}
