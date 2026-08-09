<?php

namespace App\Support;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\GroupStatement;
use App\Filament\Resources\AccountResource;
use App\Filament\Resources\DebtResource;
use App\Filament\Resources\IncomeResource;
use App\Filament\Resources\LoanResource;
use App\Filament\Resources\PayableResource;
use App\Filament\Resources\ReceivableResource;
use App\Filament\Resources\SavingResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The words in the help centre.
 *
 * Kept apart from the page that renders it so the writing can be edited without
 * touching any layout, and so the whole thing can be checked by a test — every
 * link has to point at a screen that exists, and no topic may be shown to
 * someone who cannot open the screen it sends them to.
 *
 * House style, since this is the one place in the codebase written for members
 * rather than for developers:
 *
 *   - No internal words. It is a fund, not an account; money in, not a
 *     receivable; what someone owes, not a debt record.
 *   - Every task is a numbered set of steps that names what is on screen.
 *   - Every topic that can be acted on ends in a link straight to the screen.
 *   - Say what a thing means, not what it is called.
 */
final class HelpTopics
{
    public const EVERYONE = 'everyone';

    public const TREASURER = 'treasurer';

    /**
     * @return Collection<int, array>
     */
    public static function sections(?User $user = null): Collection
    {
        $isTreasurer = (bool) $user?->isAdmin();

        return collect(self::all())
            ->map(function (array $section) use ($isTreasurer) {
                $section['topics'] = collect($section['topics'])
                    ->filter(fn (array $topic) => $isTreasurer || ($topic['audience'] ?? self::EVERYONE) === self::EVERYONE)
                    ->values()
                    ->all();

                return $section;
            })
            ->filter(fn (array $section) => $section['topics'] !== [])
            ->values();
    }

    /**
     * Every section, before any filtering. Used by the tests.
     *
     * @return array<int, array>
     */
    public static function all(): array
    {
        return [
            [
                'id' => 'start-here',
                'title' => 'Start here',
                'icon' => 'heroicon-o-map',
                'blurb' => 'What this app is for, and how to find your way around it.',
                'topics' => [
                    [
                        'id' => 'what-is-this',
                        'title' => 'What this app does',
                        'audience' => self::EVERYONE,
                        'summary' => 'It keeps the record of everyone’s money in one place: what each member has paid in, what the group has spent, who has borrowed, and who still owes.',
                        'steps' => [],
                        'notes' => [
                            'Everything is in Kenyan Shillings.',
                            'Nothing here is a guess. Every figure is worked out from an entry someone recorded, which is why the same number reads the same on every screen.',
                        ],
                        'image' => 'overview.webp',
                        'link' => ['label' => 'Go to the Overview', 'url' => fn () => Dashboard::getUrl()],
                    ],
                    [
                        'id' => 'finding-your-way',
                        'title' => 'Finding your way around',
                        'audience' => self::EVERYONE,
                        'summary' => 'The menu down the left is grouped by what you are trying to do, not by accounting terms.',
                        'steps' => [
                            '**Overview** — where the group stands today, and buttons to start the common jobs.',
                            '**Money in** — payments received from members, and anything else the group earned.',
                            '**Money out** — group expenses charged to members.',
                            '**Loans** — money lent to members, and what is still owed.',
                            '**Members** — the list of everyone, and a full statement for each person.',
                            '**Reports** — the group statement to print or share, and the full history.',
                            '**Setup** — the funds you collect into. Set once, rarely touched.',
                        ],
                        'notes' => [
                            'You will not see all of these. The menu only shows what applies to you: Members, Group income, the group statement and Setup are there for whoever keeps the books. Everything you do see is about your own money.',
                            'On a phone the menu is hidden behind the ☰ button at the top left.',
                            'Press Ctrl+K (⌘K on a Mac) anywhere to search.',
                        ],
                    ],
                    [
                        'id' => 'what-the-numbers-mean',
                        'title' => 'What the numbers on the Overview mean',
                        'audience' => self::TREASURER,
                        'summary' => 'The four boxes across the top each answer a different question. They are not meant to add up to each other.',
                        'steps' => [
                            '**Cash in savings** — money the group is holding on members’ behalf right now.',
                            '**Group net worth** — everything members have put in, less everything paid out.',
                            '**Owed to the group** — loans and unpaid amounts still to come back.',
                            '**Collected this month** — money actually received since the 1st.',
                        ],
                        'notes' => [
                            '“Collected this month” counts every shilling that came in. A fund’s “Collected” figure on the Funds screen is different — that one is what is left after the expenses paid out of it.',
                        ],
                        'image' => 'overview-stats.webp',
                    ],
                ],
            ],

            [
                'id' => 'money-in',
                'title' => 'Taking money in',
                'icon' => 'heroicon-o-arrow-down-tray',
                'blurb' => 'Recording what members have paid, and fixing it when it goes in wrong.',
                'topics' => [
                    [
                        'id' => 'record-a-payment',
                        'title' => 'Record money a member has paid',
                        'audience' => self::TREASURER,
                        'summary' => 'The job you do most. You can enter a whole meeting’s worth of payments in one go.',
                        'steps' => [
                            'Open **Money in → Collections** and press **Record money in**.',
                            'Leave the type as **Money received**.',
                            'Set the **month** the money is for. This is asked once and applies to every row below it.',
                            'On the row, pick the **member**, the **fund** they are paying into, and type the **amount**.',
                            'Choose how they paid under **Paid by** — cash, M-PESA, bank, and so on.',
                            'Press **Add another member** for each further person.',
                            'Check the **Total for this batch** against the cash or the M-PESA messages.',
                            'Press **Record entries**.',
                        ],
                        'notes' => [
                            'The month is the month the money is *for*, which is not always the month you are entering it in.',
                            'Under the amount box the app shows what that member has already paid into that fund, so you can spot a double entry before you save.',
                            'Nothing is saved until you press Record entries.',
                        ],
                        'image' => 'record-money-in.webp',
                        'link' => ['label' => 'Record money in', 'url' => fn () => ReceivableResource::getUrl('create')],
                    ],
                    [
                        'id' => 'record-arrears',
                        'title' => 'Record what a member still owes',
                        'audience' => self::TREASURER,
                        'summary' => 'Use this when someone has not paid and you want it on the record.',
                        'steps' => [
                            'Open **Record money in** as above.',
                            'At the top, switch the type to **Arrears owed**.',
                            'Fill the row in the normal way with the amount they should have paid.',
                            'Press **Record entries**.',
                        ],
                        'notes' => [
                            'This does not credit them with a payment. It creates an amount they owe, which shows up under **Loans → Money owed** and on their statement.',
                            'When they later pay it, record it as a repayment rather than as a fresh collection — see “A member pays what they owe”.',
                        ],
                        'link' => ['label' => 'Record money in', 'url' => fn () => ReceivableResource::getUrl('create')],
                    ],
                    [
                        'id' => 'fix-a-collection',
                        'title' => 'Fix a payment entered by mistake',
                        'audience' => self::TREASURER,
                        'summary' => 'Entries are not edited, they are reversed. That keeps an honest trail of what happened.',
                        'steps' => [
                            'Open **Money in → Collections**.',
                            'Find the row. The search box takes a member’s name.',
                            'Press **Reverse** at the end of the row.',
                            'Read what the box tells you it will undo, then confirm.',
                            'Record the payment again correctly.',
                        ],
                        'notes' => [
                            'Reversing puts back the member’s savings, their total for that fund, and anything the entry had settled.',
                            'Rows marked **Settling a debt** cannot be reversed here, because they came from a repayment. Correct those from **Loans → Money owed** instead.',
                        ],
                        'image' => 'collections-list.webp',
                        'link' => ['label' => 'Open Collections', 'url' => fn () => ReceivableResource::getUrl()],
                    ],
                    [
                        'id' => 'group-income',
                        'title' => 'Money the group earns',
                        'audience' => self::TREASURER,
                        'summary' => 'Joining fees and loan interest are recorded for you. Anything else you can add by hand.',
                        'steps' => [
                            'Open **Money in → Group income** to see it all.',
                            'Press **Record income** to add something else the group earned — a fine, a fundraiser, bank interest.',
                        ],
                        'notes' => [
                            'This is money the group earned, as opposed to money members contributed. The two are kept apart on purpose.',
                        ],
                        'link' => ['label' => 'Open Group income', 'url' => fn () => IncomeResource::getUrl()],
                    ],
                ],
            ],

            [
                'id' => 'money-out',
                'title' => 'Paying money out',
                'icon' => 'heroicon-o-arrow-up-tray',
                'blurb' => 'Charging a group expense to the members.',
                'topics' => [
                    [
                        'id' => 'charge-an-expense',
                        'title' => 'Charge an expense to members',
                        'audience' => self::TREASURER,
                        'summary' => 'Three short steps, ending with what it will cost the group in total before anything is saved.',
                        'steps' => [
                            'Open **Money out → Payments** and press **Record money out**.',
                            'Pick the **fund** it is paid from and the **month**.',
                            'Choose how the cost is split — **Everyone pays the same**, or **Different amount each**.',
                            'Press **Next**.',
                            'If everyone pays the same: type the amount **each member** pays, and use **Leave anyone out?** to skip people.',
                            'If amounts differ: add a row per member with their own amount.',
                            'Press **Next** to see the review, then **Record payment**.',
                        ],
                        'notes' => [
                            'The amount is what **each** member pays, not the total. The review step shows the total so you can check it.',
                            'If a member has less in that fund than the charge, the shortfall becomes an amount they owe. That is normal and it is tracked for you.',
                        ],
                        'image' => 'money-out-wizard.webp',
                        'link' => ['label' => 'Record money out', 'url' => fn () => PayableResource::getUrl('create')],
                    ],
                    [
                        'id' => 'from-savings',
                        'title' => 'Taking it from savings instead',
                        'audience' => self::TREASURER,
                        'summary' => 'You can charge an expense against the savings the group already holds for a member.',
                        'steps' => [
                            'On the **Who pays** step, set the payment to come **From their savings**.',
                        ],
                        'notes' => [
                            'Only choose this when the group is moving money it already holds. If members are handing over new money, leave it as **They pay separately**.',
                        ],
                    ],
                ],
            ],

            [
                'id' => 'loans',
                'title' => 'Loans and money owed',
                'icon' => 'heroicon-o-hand-raised',
                'blurb' => 'Lending to members, and getting it back.',
                'topics' => [
                    [
                        'id' => 'give-a-loan',
                        'title' => 'Give a member a loan',
                        'audience' => self::TREASURER,
                        'summary' => 'The app tells you in plain words what they receive and what they must pay back.',
                        'steps' => [
                            'Open **Loans → Loans issued** and press **Issue a loan**.',
                            'Pick the **member**. If they already owe money, a warning appears under their name.',
                            'Type the **amount borrowed** — what they actually receive.',
                            'Say what it is for. This makes the loan list far easier to read months later.',
                            'Choose whether to **charge interest**.',
                            'Set the **due date**.',
                            'Read the sentence under **In plain terms**, then press **Issue loan**.',
                        ],
                        'notes' => [
                            'The group standard is 1% a month. Change it only if this loan was agreed on different terms.',
                            'A repayment record is opened automatically. You do not create one yourself.',
                        ],
                        'image' => 'issue-loan.webp',
                        'link' => ['label' => 'Issue a loan', 'url' => fn () => LoanResource::getUrl('create')],
                    ],
                    [
                        'id' => 'record-repayment',
                        'title' => 'A member pays what they owe',
                        'audience' => self::TREASURER,
                        'summary' => 'Two clicks from anywhere the person appears. Part payments are fine.',
                        'steps' => [
                            'Open **Loans → Money owed**. You can also do this from the **Who owes the group** list on the Overview.',
                            'Find the member and press **Record repayment**.',
                            'Type the **amount repaid**. You cannot enter more than they owe.',
                            'Say where the money came from — a fresh payment, or their savings.',
                            'Check the sentence under **What this will do**, then press **Record repayment**.',
                        ],
                        'notes' => [
                            'A fresh payment also appears under **Collections**, because it is money coming in.',
                            'Money taken from their savings does not, because the group already held it.',
                            'Part payments are recorded as **Partly repaid** and the rest stays owing.',
                        ],
                        'image' => 'record-repayment.webp',
                        'link' => ['label' => 'Open Money owed', 'url' => fn () => DebtResource::getUrl()],
                    ],
                    [
                        'id' => 'interest',
                        'title' => 'How interest is added',
                        'audience' => self::TREASURER,
                        'summary' => 'Interest on anything still owed is added by the app, once a month.',
                        'steps' => [
                            'It runs by itself on the 1st of each month and adds 1% to anything still owed.',
                            'To catch up by hand: open **Loans → Money owed**, tick the rows, and choose **Add this month’s interest**.',
                        ],
                        'notes' => [
                            'Amounts already settled are skipped.',
                            'Interest charged is recorded as money the group earned, under **Group income**.',
                        ],
                        'link' => ['label' => 'Open Money owed', 'url' => fn () => DebtResource::getUrl()],
                    ],
                    [
                        'id' => 'why-do-i-owe',
                        'title' => 'Why does it say I owe money?',
                        'audience' => self::EVERYONE,
                        'summary' => 'Usually because an expense was charged that came to more than you had in that fund.',
                        'steps' => [
                            'Say you had paid 4,000 into Bereavement.',
                            'The group then charged everyone 5,000 for a bereavement.',
                            'Your 4,000 covered part of it, so the remaining 1,000 shows as owing.',
                            'When you pay that 1,000, it clears and is recorded as money you paid in.',
                        ],
                        'notes' => [
                            'You can see the whole picture on your own statement.',
                        ],
                    ],
                ],
            ],

            [
                'id' => 'members',
                'title' => 'Members',
                'icon' => 'heroicon-o-user-group',
                'blurb' => 'Adding people, and seeing where each of them stands.',
                'topics' => [
                    [
                        'id' => 'add-a-member',
                        'title' => 'Add a member',
                        'audience' => self::TREASURER,
                        'summary' => 'Name and joining fee are all that is really needed.',
                        'steps' => [
                            'Open **Members** and press **Add a member**.',
                            'Type their **full name**.',
                            'Add a phone number and email if you have them. An email is only needed if they will sign in.',
                            'Choose the **access level** — see “Letting a member sign in”.',
                            'Type the **joining fee** they paid.',
                            'Press **Add member**.',
                        ],
                        'notes' => [
                            'The joining fee is recorded as money the group earned and credited to them straight away.',
                        ],
                        'link' => ['label' => 'Add a member', 'url' => fn () => UserResource::getUrl('create')],
                    ],
                    [
                        'id' => 'member-statement',
                        'title' => 'See where a member stands',
                        'audience' => self::TREASURER,
                        'summary' => 'One page with everything about one person. This is the page to turn the screen around and show them.',
                        'steps' => [
                            'Open **Members**.',
                            'Press **Statement** at the end of their row.',
                        ],
                        'notes' => [
                            'It shows their savings, what they are worth, what they have put into each fund, anything they owe, and their recent payments.',
                            'It is read-only, so nothing can be changed by accident while someone is looking at it.',
                        ],
                        'image' => 'member-statement.webp',
                        'link' => ['label' => 'Open Members', 'url' => fn () => UserResource::getUrl()],
                    ],
                    [
                        'id' => 'inactive-member',
                        'title' => 'Someone has stopped contributing',
                        'audience' => self::TREASURER,
                        'summary' => 'Mark them Inactive. Do not delete them.',
                        'steps' => [
                            'Open **Members** and press **Edit** on their row.',
                            'Change **Membership status** to **Inactive**.',
                            'Save.',
                        ],
                        'notes' => [
                            'Deleting a member takes their whole history with them — every payment, loan and statement. Inactive keeps the record and simply takes them out of the active list.',
                        ],
                        'link' => ['label' => 'Open Members', 'url' => fn () => UserResource::getUrl()],
                    ],
                    [
                        'id' => 'access-levels',
                        'title' => 'Letting a member sign in',
                        'audience' => self::TREASURER,
                        'summary' => 'There are two levels, and the difference matters.',
                        'steps' => [
                            '**Treasurer / Admin** — can record money in and out, issue loans, and see everything.',
                            '**Member** — can only ever see their own savings, payments and loans. They cannot see anybody else’s money and cannot record anything.',
                        ],
                        'notes' => [
                            'Set a password on their profile so they can sign in. Without one they simply appear in the records.',
                        ],
                        'image' => 'members-list.webp',
                        'link' => ['label' => 'Open Members', 'url' => fn () => UserResource::getUrl()],
                    ],
                    [
                        'id' => 'my-own-money',
                        'title' => 'Seeing your own money',
                        'audience' => self::EVERYONE,
                        'summary' => 'Everything in this app is yours alone. Other members’ figures are never shown to you, on any screen.',
                        'steps' => [
                            '**Overview** — your savings, what you are worth, what you have put into each fund and anything you owe.',
                            '**Money in → Collections** — every payment of yours the group has recorded, and what it was for.',
                            '**Money out → Payments** — group expenses that were charged to you.',
                            '**Loans → Loans issued** — anything you have borrowed.',
                            '**Loans → Money owed** — what is still to pay, and by when.',
                            '**Reports → Savings ledger** — every movement in and out, in the order it happened.',
                        ],
                        'notes' => [
                            'If a figure looks wrong, the savings ledger is the place to check: it shows what each entry did and what your balance was straight afterwards. Take it to whoever keeps the books.',
                        ],
                        'link' => ['label' => 'Go to the Overview', 'url' => fn () => Dashboard::getUrl()],
                    ],
                ],
            ],

            [
                'id' => 'reports',
                'title' => 'Reports and records',
                'icon' => 'heroicon-o-document-chart-bar',
                'blurb' => 'What to print for a meeting, and where to look when a figure is questioned.',
                'topics' => [
                    [
                        'id' => 'group-statement',
                        'title' => 'The statement for a meeting',
                        'audience' => self::TREASURER,
                        'summary' => 'Every member against every fund, on one grid. Download it as Excel or PDF.',
                        'steps' => [
                            'Open **Reports → Group statement**.',
                            'Use **Find a member** to jump to someone.',
                            'Press **Download as Excel** or **Download as PDF**.',
                        ],
                        'notes' => [
                            'Scroll sideways to see all the funds. The member column stays put and the totals row stays at the bottom.',
                            'Click any name to open that person’s own statement.',
                        ],
                        'image' => 'group-statement.webp',
                        'link' => ['label' => 'Open Group statement', 'url' => fn () => GroupStatement::getUrl()],
                    ],
                    [
                        'id' => 'savings-ledger',
                        'title' => 'Checking how a figure got there',
                        'audience' => self::EVERYONE,
                        'summary' => 'The savings ledger lists every movement of money in the order it happened.',
                        'steps' => [
                            'Open **Reports → Savings ledger**.',
                            'Filter by member or by date to narrow it down.',
                        ],
                        'notes' => [
                            'Use this when someone questions a balance. It shows what each transaction did and what the balance was straight afterwards.',
                            'Nothing here can be typed in by hand — every line is written by a payment, an expense, a loan or a repayment.',
                        ],
                        'link' => ['label' => 'Open Savings ledger', 'url' => fn () => SavingResource::getUrl()],
                    ],
                    [
                        'id' => 'funds',
                        'title' => 'Setting up a fund',
                        'audience' => self::TREASURER,
                        'summary' => 'A fund is a pot you collect into — Bereavement, Insurance, Administration.',
                        'steps' => [
                            'Open **Setup → Funds** and press **Create a fund**.',
                            'Give it a name members will recognise on their statement.',
                        ],
                        'notes' => [
                            'The Funds screen also shows how much has been collected into each and how much has been paid out.',
                        ],
                        'link' => ['label' => 'Open Funds', 'url' => fn () => AccountResource::getUrl()],
                    ],
                ],
            ],

            [
                'id' => 'questions',
                'title' => 'Common questions',
                'icon' => 'heroicon-o-question-mark-circle',
                'blurb' => 'The things people ask most often.',
                'topics' => [
                    [
                        'id' => 'savings-vs-net-worth',
                        'title' => 'What is the difference between savings and net worth?',
                        'audience' => self::EVERYONE,
                        'summary' => '**Savings** is cash you can draw on. **Net worth** is everything you have put in, less everything drawn out.',
                        'steps' => [],
                        'notes' => [
                            'They are different on purpose. You can be worth a good deal in the group while holding very little cash in savings.',
                        ],
                    ],
                    [
                        'id' => 'two-collected-figures',
                        'title' => 'Why do two screens show different “collected” amounts?',
                        'audience' => self::TREASURER,
                        'summary' => 'They answer different questions.',
                        'steps' => [
                            '**Collected this month** on the Overview — every shilling received since the 1st.',
                            '**Collected** on the Funds screen — what is left in that fund after the expenses paid out of it.',
                        ],
                        'notes' => [
                            'So a fund can read zero while the month shows a healthy figure: the money came in and went straight back out.',
                        ],
                    ],
                    [
                        'id' => 'nothing-saved',
                        'title' => 'I filled in a form but nothing was saved',
                        'audience' => self::TREASURER,
                        'summary' => 'Nothing is stored until you press the button at the bottom.',
                        'steps' => [
                            'Check you pressed **Record entries**, **Record payment** or **Issue loan**.',
                            'If a box is outlined in red, read the message under it and fix that first.',
                            'If you leave a half-finished form the app warns you before losing it.',
                        ],
                    ],
                    [
                        'id' => 'on-a-phone',
                        'title' => 'Using it on a phone',
                        'audience' => self::EVERYONE,
                        'summary' => 'Everything works on a phone. A couple of things move around.',
                        'steps' => [
                            'The menu is behind the ☰ button at the top left.',
                            'Long tables scroll sideways — drag the table itself, not the page.',
                            'Some columns are hidden on a small screen to keep the buttons reachable.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
