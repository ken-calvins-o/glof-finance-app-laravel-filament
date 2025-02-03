<?php

namespace App\Models;

use App\Enums\FrequencyTypeEnum;
use App\Enums\MemberStatus;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;

class Account extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'is_general' => 'boolean',
        'create_income' => 'boolean',
        'description' => 'string',
        'frequency_type' => FrequencyTypeEnum::class,
    ];

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'account_user')
            ->using(AccountUser::class);
    }
    public function years(): BelongsToMany
    {
        return $this->belongsToMany(Year::class, 'account_year')
            ->using(AccountYear::class);
    }

    public static function getForm(): array
    {
        return [
            Section::make('Account Details')
                ->columns(['md' => 2, 'lg' => 2])
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->label('Name of account')
                        ->maxLength(255),
                    Select::make('frequency_type')
                        ->label('Frequency')
                        ->enum(FrequencyTypeEnum::class)
                        ->options(collect(FrequencyTypeEnum::cases())->mapWithKeys(function ($case) {
                            return [$case->value => ucwords(str_replace('_', ' ', $case->value))];
                        }))
                        ->required(),
                    Textarea::make('description')
                        ->label('Description of account')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Fieldset::make('Account Type')
                        ->schema([
                            ToggleButtons::make('is_general')
                                ->label('Account Type')
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
                        ])
                        ->visible(fn($state) => $state['is_general']),
                    Fieldset::make('Income Enablement')
                        ->schema([
                            ToggleButtons::make('create_income')
                                ->label('Allow Income Generation')
                                ->boolean()
                                ->default(false)
                                ->inline()
                                ->helperText('This setting generates an income record for the linked member *')
                                ->grouped()
                                ->reactive()
                                ->columnSpanFull(),
                        ]),
                    Fieldset::make('Billing Type')
                        ->schema([
                            ToggleButtons::make('billing_type')
                                ->label('Choose Billing Type')
                                ->boolean()
                                ->options([
                                    false => 'Credit',
                                    true => 'Debit',
                                ])
                                ->default(true)
                                ->inline()
                                ->grouped()
                                ->reactive()
                                ->columnSpanFull(),
                        ])
                        ->visible(fn($state) => $state['is_general']),
                    Fieldset::make('Cost Overview')
                        ->schema([
                            TextInput::make('amount_due')
                                ->label('Amount Charged Per Member')
                                ->required()
                                ->minValue(1)
                                ->numeric()
                                ->hintIcon('heroicon-o-currency-dollar')
                                ->prefix('KES')
                                ->reactive()
                                ->debounce(500)
                                ->afterStateUpdated(function ($state, callable $set) {
                                    // Calculate and update the budget based on the amount_due for general accounts
                                    $budget = $state * User::count();
                                    $set('budget', $budget);
                                }),
                            TextInput::make('budget')
                                ->label('Estimated Group Budget')
                                ->numeric()
                                ->hintIcon('heroicon-o-currency-dollar')
                                ->prefix('KES')
                                ->disabled(),
                        ])
                        ->visible(fn($state) => $state['is_general']),
                    Fieldset::make('Custom Account Details')
                        ->schema([
                            Repeater::make('users')
                                ->label('Select Members')
                                ->visible(fn() => true)
                                ->schema([
                                    Fieldset::make('Member Payment Information')
                                        ->schema([
                                            Select::make('user_id')
                                                ->label('Member')
                                                ->options(User::query()
                                                    ->where('member_status', MemberStatus::Active) // Filter users by active status
                                                    ->pluck('name', 'id')) // Fetch users manually
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->columnSpan(1),
                                            TextInput::make('amount_due')
                                                ->label('Amount')
                                                ->required()
                                                ->numeric()
                                                ->minValue(1)
                                                ->hintIcon('heroicon-o-currency-dollar')
                                                ->prefix('KES')
                                                ->reactive()
                                                ->debounce(500)
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    // Set budget to the same value as amount_due directly for custom accounts
                                                    $set('budget', $state);
                                                }),
                                        ]),
                                    Fieldset::make('Billing Period & Account Type')
                                        ->schema([
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
                                            ToggleButtons::make('billing_type')
                                                ->label('Choose Billing Type')
                                                ->boolean()
                                                ->options([
                                                    true => 'Debit',
                                                    false => 'Credit',
                                                ])
                                                ->default(true)
                                                ->inline()
                                                ->grouped()
                                                ->reactive(),
                                        ])
                                        ->columns(3),
                                ])
                                ->columns(3)
                                ->createItemButtonLabel('Add More Details')
                                ->columnSpanFull(),
                        ])
                        ->visible(fn($state) => !$state['is_general']),
                ]),
        ];
    }
}
