<?php

namespace App\Filament\Pages;

use App\Filament\Navigation;
use App\Models\Account;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Saving;
use App\Models\User;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Collection;

/**
 * The group statement — every member against every fund, in one grid.
 *
 * This is the page that gets projected at a meeting, so the changes are mostly
 * about legibility:
 *
 *  - The Blade view used to call `new StaticReadOnlyTable` and re-run the whole
 *    query set a second time purely to compute the totals row, so opening the
 *    page cost twice what it needed to. The data is now built once and handed
 *    to the view, totals included.
 *  - Every colour in the markup was hard-coded (bg-white, text-gray-700), so in
 *    dark mode the page rendered as white text on white. It now uses the theme.
 *  - Amounts were left-aligned in proportional figures, which is the one thing
 *    guaranteed to make a column of money unreadable. They are right-aligned
 *    and tabular now, and the member column stays pinned while you scroll
 *    sideways through the funds.
 */
class GroupStatement extends Page
{
    protected static string $view = 'filament.pages.group-statement';

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $activeNavigationIcon = 'heroicon-s-table-cells';

    protected static ?string $navigationGroup = Navigation::REPORTS;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Group statement';

    protected ?string $subheading = 'Every member against every fund. Scroll sideways to see all funds; the member column stays put.';

    protected static ?string $slug = 'group-statement';

    public ?string $search = '';

    public function getMaxContentWidth(): MaxWidth
    {
        // This page is a wide grid and nothing else, so let it use the screen.
        return MaxWidth::Full;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * Build the grid once: one row per member, one column per fund, plus the
     * running figures that live outside the funds.
     *
     * @return array{rows: Collection, accounts: Collection, totals: Collection}
     */
    public function getStatement(): array
    {
        $accounts = Account::orderBy('name')->get();

        $users = User::query()
            ->when(filled($this->search), fn ($query) => $query->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name')
            ->get();

        $userIds = $users->pluck('id')->all();

        // One query per concept, keyed for lookup, rather than a query per
        // member per fund.
        $collections = \App\Models\AccountCollection::whereIn('user_id', $userIds)
            ->get()
            ->keyBy(fn ($row) => $row->user_id . '-' . $row->account_id);

        $latestSavings = Saving::whereIn('user_id', $userIds)
            ->whereIn('id', Saving::query()->toBase()->selectRaw('MAX(id) as id')->groupBy('user_id'))
            ->get()
            ->keyBy('user_id');

        $loanBalances = Loan::whereIn('user_id', $userIds)
            ->orderByDesc('created_at')
            ->get()
            ->unique('user_id')
            ->pluck('balance', 'user_id');

        // A member's live loan position lives on their credited-loan debt.
        $loanDebts = Debt::whereIn('user_id', $userIds)
            ->whereNull('account_id')
            ->orderByDesc('created_at')
            ->get()
            ->unique('user_id')
            ->pluck('outstanding_balance', 'user_id');

        $rows = $users->map(function (User $user) use ($accounts, $collections, $latestSavings, $loanBalances, $loanDebts) {
            $funds = $accounts->mapWithKeys(fn (Account $account) => [
                $account->id => (float) ($collections->get($user->id . '-' . $account->id)?->amount ?? 0),
            ]);

            $saving = $latestSavings->get($user->id);

            $loan = $loanDebts->has($user->id)
                ? max(0, (float) $loanDebts->get($user->id))
                : (float) ($loanBalances->get($user->id) ?? 0);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar_url,
                'registration_fee' => (float) ($user->registration_fee ?? 0),
                'funds' => $funds,
                'loan' => $loan,
                'savings' => (float) ($saving->balance ?? 0),
                'net_worth' => (float) ($saving->net_worth ?? 0),
            ];
        });

        $totals = collect([
            'registration_fee' => $rows->sum('registration_fee'),
            'loan' => $rows->sum('loan'),
            'savings' => $rows->sum('savings'),
            'net_worth' => $rows->sum('net_worth'),
            'funds' => $accounts->mapWithKeys(fn (Account $account) => [
                $account->id => $rows->sum(fn (array $row) => $row['funds'][$account->id] ?? 0),
            ]),
        ]);

        return ['rows' => $rows, 'accounts' => $accounts, 'totals' => $totals];
    }

    /**
     * Flatten the grid for export, keyed by the human column names.
     */
    public function getExportData(): array
    {
        ['rows' => $rows, 'accounts' => $accounts] = $this->getStatement();

        return $rows->map(function (array $row) use ($accounts) {
            $export = ['Member' => $row['name']];

            foreach ($accounts as $account) {
                $export[$account->name] = Money::roundToNearest05($row['funds'][$account->id] ?? 0);
            }

            $export['Joining fee'] = Money::roundToNearest05($row['registration_fee']);
            $export['Loan owing'] = Money::roundToNearest05($row['loan']);
            $export['Savings'] = Money::roundToNearest05($row['savings']);
            $export['Net worth'] = Money::roundToNearest05($row['net_worth']);

            return $export;
        })->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Download as Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $rows = $this->getExportData();

                    return response()->streamDownload(function () use ($rows) {
                        $file = fopen('php://output', 'w');

                        if (empty($rows)) {
                            fputcsv($file, ['Member']);
                            fclose($file);

                            return;
                        }

                        fputcsv($file, array_keys($rows[0]));

                        foreach ($rows as $row) {
                            fputcsv($file, $row);
                        }

                        fclose($file);
                    }, 'group_statement_' . now()->format('Y_m_d_His') . '.csv');
                }),

            Action::make('export_pdf')
                ->label('Download as PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    ['rows' => $rows, 'accounts' => $accounts, 'totals' => $totals] = $this->getStatement();

                    $pdf = Pdf::loadView('pdf.group-statement', [
                        'rows' => $rows,
                        'accounts' => $accounts,
                        'totals' => $totals,
                    ])->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        'group_statement_' . now()->format('Y_m_d_His') . '.pdf',
                    );
                }),
        ];
    }
}
