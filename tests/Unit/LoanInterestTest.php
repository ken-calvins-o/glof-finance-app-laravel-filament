<?php

namespace Tests\Unit;

use App\Models\Loan;
use PHPUnit\Framework\TestCase;

/**
 * Guards the meaning of the `interest` column.
 *
 * It holds the monthly *rate*, not a shilling amount. Creation used to store
 * the shilling amount there while the edit page read it back as a percentage,
 * so re-saving a loan multiplied its balance by the interest figure. These
 * tests pin the arithmetic down on both sides.
 */
class LoanInterestTest extends TestCase
{
    public function test_interest_is_a_percentage_of_the_amount(): void
    {
        $this->assertEqualsWithDelta(500.0, Loan::interestAmount(50_000, 1, true), 0.001);
        $this->assertEqualsWithDelta(2_500.0, Loan::interestAmount(50_000, 5, true), 0.001);
    }

    public function test_no_interest_is_charged_when_the_loan_is_interest_free(): void
    {
        $this->assertSame(0.0, Loan::interestAmount(50_000, 1, false));
        $this->assertEqualsWithDelta(50_000.0, Loan::repayableAmount(50_000, 1, false), 0.001);
    }

    public function test_the_repayable_amount_is_the_loan_plus_its_interest(): void
    {
        $this->assertEqualsWithDelta(50_500.0, Loan::repayableAmount(50_000, 1, true), 0.001);
    }

    /**
     * The specific regression: a KES 50,000 loan at 1% must never be able to
     * produce a balance in the millions, which is what happened when 500 was
     * stored in the rate column and then read back as "500%".
     */
    public function test_the_balance_stays_stable_when_recomputed_from_the_stored_rate(): void
    {
        $amount = 50_000.0;
        $rate = 1.0;

        $balance = Loan::repayableAmount($amount, $rate, true);
        $recomputed = Loan::repayableAmount($amount, $rate, true);

        $this->assertEqualsWithDelta($balance, $recomputed, 0.001);
        $this->assertLessThan(51_000, $recomputed);
    }

    public function test_amounts_are_rounded_to_the_cent(): void
    {
        // 333.33 at 1% is 3.3333, which must not leak extra decimal places
        // into a money column.
        $this->assertSame(3.33, Loan::interestAmount(333.33, 1, true));
    }
}
