<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Loan;
use App\Models\Saving;
use App\Models\User;
use App\Models\Receivable;
use Filament\Pages\Page;

class StaticReadOnlyTable extends Page
{
    protected static string $view = 'filament.pages.static-read-only-table';

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
                $latestContribution = Receivable::where('user_id', $user->id)
                    ->where('account_id', $account->id)
                    ->latest('created_at') // Get the latest record
                    ->value('total_amount_contributed'); // Assuming this field exists in the receivables table

                // Add to the row
                $row['Account ' . $account->id] = $latestContribution ?? 0.00;
            }

            // Fetch "Savings Balance" from the savings table/model
            $savingsBalance = Saving::where('user_id', $user->id)->value('balance');
            $row['Savings Balance'] = $savingsBalance ?? 0.00; // Default to 0.00 if null

            // Fetch "Net Worth" from the savings table/model
            $netWorth = Saving::where('user_id', $user->id)->value('net_worth');
            $row['Net Worth'] = $netWorth ?? 0.00; // Default to 0.00 if null

            $loanBalance = Loan::where('user_id', $user->id)->value('balance');
            $row['Loan Balance'] = $loanBalance ?? 0.00;

            $data[] = $row; // Add user row to data
        }

        return [$data, $accounts];
    }
}
