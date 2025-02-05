<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\AccountCollection;
use App\Models\Loan;
use App\Models\Saving;
use App\Models\User;
use App\Models\Receivable;
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

        // Get all users (to create rows)
        $users = User::all();

        // Preparing the table data
        $data = [];
        foreach ($users as $user) {
            $row = ['User' => $user->name]; // Assuming "name" exists in the users table

            // Add data for each account
            foreach ($accounts as $account) {
                $latestContribution = AccountCollection::where('user_id', $user->id)
                    ->where('account_id', $account->id)
                    ->value('amount'); // Assuming this field exists in the receivables table

                // Add to the row
                $row['Account ' . $account->id] = $latestContribution ?? 0.00;
            }

            // Fetch "Savings Balance" from the savings table/model
            $savingsBalance = Saving::where('user_id', $user->id)->value('balance');
            $row['Savings'] = $savingsBalance ?? 0.00; // Default to 0.00 if null

            // Fetch the latest "Net Worth" from the savings table/model
            $netWorth = Saving::where('user_id', $user->id)
                ->latest('id') // Get the latest record based on primary key "id"
                ->value('net_worth');

            $row['Net Worth'] = $netWorth ?? 0.00; // Default to 0.00 if null

            $loanBalance = Loan::where('user_id', $user->id)->value('balance');
            $row['Loan'] = $loanBalance ?? 0.00;

            $data[] = $row; // Add user row to data
        }

        return [$data, $accounts];
    }
}
