<?php

namespace App\Models;

use App\Enums\MemberStatus;
use Filament\Forms\Components\Fieldset;
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
                            ToggleButtons::make('is_general')
                                ->label('Debit Type')
                                ->boolean()
                                ->options([
                                    true => 'General',
                                    false => 'Custom',
                                ])
                                ->default(true)
                                ->inline()
                                ->grouped()
                                ->reactive()
                                ->columnSpanFull(),
                        ]),
                    Fieldset::make('Exclude/Leave out members')
                        ->schema([
                            Fieldset::make('Exclude/Leave out members')
                                ->schema([
                                    Select::make('user_id')
                                        ->label('Select Members')
                                        ->multiple()
                                        ->reactive()
                                        ->options(
                                            User::query()
                                                ->where('member_status', MemberStatus::Active) // Filter users by active status
                                                ->pluck('name', 'id')
                                                ->toArray() // Fetch `name` and `id` as key-value pairs
                                        )
                                        ->maxItems(5),
                                ])
                                ->visible(fn($state) => $state['is_general']),
                        ])
                        ->visible(fn($state) => $state['is_general']),
                    Fieldset::make('Payment Mode')
                        ->schema([
                            ToggleButtons::make('from_savings')
                                ->boolean()
                                ->label('Do you want to debit the outstanding balance of the associated members from their savings account?')
                                ->default(false)
                                ->inline()
                                ->grouped()
                                ->columnSpanFull(),
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
                                        ->options(function (callable $get) {
                                            // Fetch the `account_id` value from the main form
                                            $accountId = $get('../../account_id'); // Access parent context outside the Repeater

                                            // Return empty options if no account is selected
                                            if (!$accountId) {
                                                return [];
                                            }

                                            // Fetch users linked to the given account_id via `AccountUser` pivot model
                                            return AccountUser::where('account_id', $accountId)
                                                ->with('user:id,name') // Eager load the associated User model for `id` and `name`
                                                ->get()
                                                ->mapWithKeys(fn($accountUser) => [$accountUser->user->id => $accountUser->user->name])
                                                ->toArray();
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                    TextInput::make('amount_due')
                                        ->label('Debit Amount')
                                        ->required()
                                        ->numeric()
                                        ->minValue(1)
                                        ->hintIcon('heroicon-o-currency-dollar')
                                        ->prefix('KES')
                                        ->reactive()
                                        ->debounce(500),
                                    ToggleButtons::make('from_savings')
                                        ->label('Use savings')
                                        ->boolean()
                                        ->default(false)
                                        ->inline()
                                        ->grouped()
                                        ->reactive(),
                                ])->columns(3),
                        ])
                        ->createItemButtonLabel('Add More Details')
                        ->columnSpanFull(),
                ])
                ->visible(fn($state) => !$state['is_general']),
        ];
    }
}
