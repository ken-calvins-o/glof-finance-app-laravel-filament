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

        // Preparing the table data
        $data = [];

        foreach ($users as $user) {
            $row = ['User' => $user->name]; // Assuming "name" exists in the users table

            // Add data for each account
            foreach ($accounts as $account) {
                $latestContribution = AccountCollection::where('user_id', $user->id)
                    ->where('account_id', $account->id)
                    ->value('amount'); // Assuming this field exists in the receivables table

                // Use the actual account name as the key
                $row[$account->name] = $latestContribution ?? 0.00;
            }

            // Registration Fee from User model
            $row['Registration Fee'] = $user->registration_fee ?? 0.00;

            // Fetch "Savings Balance" from the savings table/model
            $savingsBalance = Saving::where('user_id', $user->id)
                ->latest('id') // Get the latest record based on primary key "id"
                ->value('balance');

            $row['Savings'] = $savingsBalance ?? 0.00; // Default to 0.00 if null

            // Fetch the latest "Net Worth" from the savings table/model
            $netWorth = Saving::where('user_id', $user->id)
                ->latest('id') // Get the latest record based on primary key "id"
                ->value('net_worth');
            $row['Net Worth'] = $netWorth ?? 0.00; // Default to 0.00 if null

            // Fetch loan balance
            $loanBalance = Loan::where('user_id', $user->id)->value('balance');
            $row['Loan'] = $loanBalance ?? 0.00;

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
