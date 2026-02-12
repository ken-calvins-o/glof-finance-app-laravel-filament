<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\AccountCollection;
use App\Models\Loan;
use App\Models\Saving;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;

class StaticReadOnlyTable extends Page
{
    protected static string $view = 'filament.pages.static-read-only-table';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    public static function getNavigationLabel(): string
    {
        return 'Group Statement';
    }

    public function getTableData(): array
    {
        // Get all accounts (to create dynamic columns)
        $accounts = Account::all();

        // Get all users and sort them alphabetically by name
        $users = User::all()->sortBy('name');

        $userIds = $users->pluck('id')->all();
        $accountIds = $accounts->pluck('id')->all();

        // Batch fetch latest AccountCollection per user+account (ordered by id desc to get latest)
        $accountCollections = AccountCollection::whereIn('user_id', $userIds)
            ->whereIn('account_id', $accountIds)
            ->orderByDesc('id')
            ->get()
            // ensure unique per user-account keeping the latest
            ->unique(function ($item) {
                return $item->user_id . '-' . $item->account_id;
            })
            ->keyBy(function ($item) {
                return $item->user_id . '-' . $item->account_id;
            });

        // Batch fetch latest Saving per user
        $savingsMap = Saving::whereIn('user_id', $userIds)
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->pluck('balance', 'user_id');

        // Batch fetch latest net_worth per user
        $netWorthMap = Saving::whereIn('user_id', $userIds)
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->pluck('net_worth', 'user_id');

        // Batch fetch latest Loan balance per user
        $loanMap = Loan::whereIn('user_id', $userIds)
            ->orderByDesc('created_at')
            ->get()
            ->unique('user_id')
            ->pluck('balance', 'user_id');

        // Batch fetch latest credited Debt outstanding_balance per user (account_id IS NULL)
        $debtMap = \App\Models\Debt::whereIn('user_id', $userIds)
            ->whereNull('account_id')
            ->orderByDesc('created_at')
            ->get()
            ->unique('user_id')
            ->pluck('outstanding_balance', 'user_id');

        // Preparing the table data
        $data = [];

        foreach ($users as $user) {
            $row = ['User' => $user->name];

            // Add data for each account using the preloaded AccountCollection map
            foreach ($accounts as $account) {
                $key = $user->id . '-' . $account->id;
                $latestContribution = $accountCollections->has($key) ? $accountCollections->get($key)->amount : null;
                $row[$account->name] = $latestContribution ?? 0.00;
            }

            // Registration Fee from User model
            $row['Registration Fee'] = $user->registration_fee ?? 0.00;

            // Savings and Net Worth from preloaded maps
            $row['Savings'] = $savingsMap->get($user->id, 0.00);
            $row['Net Worth'] = $netWorthMap->get($user->id, 0.00);

            // Loan: prefer credited debt outstanding_balance if available; otherwise fallback to loan balance
            $debtBalance = $debtMap->get($user->id);
            if (is_null($debtBalance)) {
                $loanBalance = $loanMap->get($user->id, 0.00);
                $row['Loan'] = $loanBalance ?? 0.00;
            } else {
                $row['Loan'] = max(0, (float) $debtBalance);
            }

            // Add user row to data
            $data[] = $row;
        }

        return [$data, $accounts];
    }

    public function getExportData(): array
    {
        [$tableData, $accounts] = $this->getTableData();

        // Export data preparation
        $exportData = [];
        foreach ($tableData as $row) {
            $exportRow = ['User' => $row['User']];

            foreach ($accounts as $account) {
                $exportRow[$account->name] = $row['Account ' . $account->id] ?? 0.00;
            }
            $exportRow['Registration Fee'] = $row['Registration Fee'] ?? 0.00;
            $exportRow['Loan'] = $row['Loan'] ?? 0.00;
            $exportRow['Savings'] = $row['Savings'] ?? 0.00;
            $exportRow['Net Worth'] = $row['Net Worth'] ?? 0.00;

            $exportData[] = $exportRow;
        }

        return $exportData;
    }

    public function getActions(): array
    {
        return [
            // CSV Export Action
            Action::make('export_csv')
                ->label('Download to Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // Use Laravel Excel to create and return export
                    $exportData = $this->getExportData();

                    return response()->streamDownload(function () use ($exportData) {
                        $file = fopen('php://output', 'w');

                        // Add a header row to the CSV file (e.g., column names)
                        fputcsv($file, array_keys($exportData[0]));

                        // Add all the rows of data
                        foreach ($exportData as $row) {
                            fputcsv($file, $row);
                        }

                        fclose($file);
                    }, 'gulf_group_statement_' . date('Y_m_d_His') . '.csv');
                }),

            // PDF Export Action
            Action::make('export_pdf')
                ->label('Export to PDF')
                ->icon('heroicon-o-document')
                ->color('danger')
                ->action(function () {
                    [$data, $accounts] = $this->getTableData();

                    // Render Blade view as HTML for Dompdf
                    $pdf = Pdf::loadView('pdf.group-statement', [
                        'data' => $data,
                        'accounts' => $accounts,
                    ]);

                    // Stream the generated PDF file to the browser for download
                    return response()->streamDownload(fn () => print($pdf->output()), 'gulf_group_statement_' . date('Y_m_d_His') . '.pdf');
                }),
        ];
    }
}
