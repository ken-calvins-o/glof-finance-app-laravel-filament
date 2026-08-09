<?php

namespace App\Filament;

/**
 * The app's information architecture, in one place.
 *
 * The old grouping followed a balance sheet — "Accounts", "Liabilities",
 * "Assets", "Members' Profile" — which is how an accountant files things, not
 * how a treasurer works. A treasurer's day is: money came in, money went out,
 * someone wants a loan, someone is repaying, and then reports at month end.
 *
 * These groups follow that sequence. Keeping the constants here means a group
 * can be renamed or re-ordered without hunting through nine resource classes.
 */
final class Navigation
{
    public const MONEY_IN = 'Money in';

    public const MONEY_OUT = 'Money out';

    public const LENDING = 'Loans';

    public const PEOPLE = 'Members';

    public const REPORTS = 'Reports';

    public const SETUP = 'Setup';

    /**
     * Ordering of the groups in the sidebar, top to bottom.
     *
     * @return array<string, int>
     */
    public static function order(): array
    {
        return [
            self::MONEY_IN => 1,
            self::MONEY_OUT => 2,
            self::LENDING => 3,
            self::PEOPLE => 4,
            self::REPORTS => 5,
            self::SETUP => 6,
        ];
    }
}
