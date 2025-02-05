<?php

namespace App\Models;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Payable extends Model
{
    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
        'from_savings' => 'boolean',
        'is_general' => 'boolean',
    ];

    public function months(): BelongsToMany
    {
        return $this->belongsToMany(Month::class, 'monthly_payable')
            ->using(MonthlyPayable::class);
    }

    public function years(): BelongsToMany
    {
        return $this->belongsToMany(Year::class, 'payable_year')
            ->using(PayableYear::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accountUser()
    {
        return $this->belongsTo(AccountUser::class, 'user_id', 'user_id')
            ->where('account_id', $this->account_id);
    }

    public static function getForm()
    {
        return [
            Section::make('Payable Details')
                ->schema([
                    Fieldset::make('Account Details')
                        ->schema([
                            Select::make('account_id')
                                ->relationship('account', 'name')
                                ->label('Account Name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive(),
                            Select::make('month_id')
                                ->label('Month')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->options(Month::all()->pluck('name', 'id')->toArray()) // Fetch months
                                ->default(Month::where('name', now()->format('F'))->value('id')), // Set the default to the current month's ID
                            Select::make('year_id')
                                ->label('Year')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->options(Year::all()->pluck('year', 'id')->toArray()) // Fetch years
                                ->default(Year::where('year', now()->year)->value('id')), // Set the default to the current year's ID
                        ])
                        ->columns(3),
                    Fieldset::make('Debit Type')
                        ->schema([
                            Radio::make('is_general')
                                ->label('Please select the type of debit')
                                ->boolean()
                                ->options([
                                    1 => 'Shared',
                                    0=> 'Custom',
                                ])
                                ->default(true)
                                ->inline()
                                ->reactive()
                                ->columnSpanFull(),
                        ]),
                    Fieldset::make('Payment Details')
                        ->schema([
                            TextInput::make('total_amount')
                                ->label('Amount')
                                ->prefix('KES')
                                ->helperText('This amount is the same for all members')
                                ->reactive()
                                ->numeric()
                                ->minValue(1),
                            ToggleButtons::make('from_savings')
                                ->boolean()
                                ->label('Use member\'s savings?')
                                ->default(false)
                                ->inline()
                                ->grouped(),
                        ])
                        ->columns(2)
                        ->visible(fn($state) => $state['is_general']),
                    Fieldset::make('Exclude/Leave out members')
                        ->schema([
                            Fieldset::make('Exclude/Leave out members')
                                ->schema([
                                    Select::make('user_id')
                                        ->label('Select Members')
                                        ->options(function () {
                                            // Fetch all users and map them to an `id => name` structure
                                            return \App\Models\User::all()
                                                ->pluck('name', 'id') // Map user `name` as the display value and `id` as the key
                                                ->toArray();
                                        })
                                        ->multiple()
                                        ->reactive()
                                        ->maxItems(5),
                                ])
                                ->visible(fn($state) => $state['is_general']),
                        ])
                        ->visible(fn($state) => $state['is_general']),
                ])
                ->columnSpanFull(),
            Fieldset::make('Custom Debit')
                ->schema([
                    Repeater::make('users')
                        ->label('Select Members')
                        ->schema([
                            Fieldset::make('Member Payment Information')
                                ->schema([
                                    Select::make('user_id')
                                        ->label('Member')
                                        ->options(function () {
                                            // Fetch all users and map them to an `id => name` structure
                                            return \App\Models\User::all()
                                                ->pluck('name', 'id') // Map user `name` as the display value and `id` as the key
                                                ->toArray();
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->reactive(),
                                    TextInput::make('total_amount')
                                        ->label('Debit Amount')
                                        ->required()
                                        ->numeric()
                                        ->minValue(1)
                                        ->hintIcon('heroicon-o-currency-dollar')
                                        ->prefix('KES')
                                        ->reactive()
                                        ->afterStateUpdated(function (callable $get, callable $set) {
                                            // Get the selected account_id and user_id
                                            $accountId = $get('../../account_id'); // `account_id` is outside the repeater; traverse hierarchy
                                            $userId = $get('user_id');

                                            if ($accountId && $userId) {
                                                // Fetch the latest "total_amount_contributed" for the selected account and user
                                                $totalContributed = \App\Models\Receivable::where('account_id', $accountId)
                                                    ->where('user_id', $userId)
                                                    ->latest('created_at') // Use the most recent record
                                                    ->value('total_amount_contributed') ?? 0; // Default to 0 if no record exists

                                                // Update the helper text dynamically
                                                $set('helperText', "Total contributed: KES " . number_format($totalContributed, 2));
                                            } else {
                                                // Clear the helper text if account or user is not selected
                                                $set('helperText', "Total contributed: KES 0.00");
                                            }
                                        })
                                        ->helperText(function (callable $get) {
                                            // Get the selected account_id and user_id
                                            $accountId = $get('../../account_id'); // `account_id` is outside the repeater; traverse hierarchy
                                            $userId = $get('user_id');

                                            if ($accountId && $userId) {
                                                // Fetch the latest "total_amount_contributed" for the selected account and user
                                                $totalContributed = \App\Models\Receivable::where('account_id', $accountId)
                                                    ->where('user_id', $userId)
                                                    ->latest('created_at') // Use the most recent record
                                                    ->value('total_amount_contributed') ?? 0; // Default to 0 if no record exists

                                                return "Total contributed: KES " . number_format($totalContributed, 2);
                                            }

                                            return "Total contributed: KES 0.00"; // Default if account or user is not set
                                        }),
                                    ToggleButtons::make('from_savings')
                                        ->label('Use savings')
                                        ->boolean()
                                        ->default(false)
                                        ->inline()
                                        ->grouped()
                                        ->reactive(),
                                ])
                                ->columns(3),
                        ])
                        ->createItemButtonLabel('Add More Details')
                        ->columnSpanFull(),
                ])
                ->visible(fn($state) => !$state['is_general']),
        ];
    }
}
