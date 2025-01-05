<?php

namespace App\Models;

use App\Enums\FrequencyTypeEnum;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'is_general' => 'boolean',
        'billing_type' => 'boolean',
        'create_income' => 'boolean',
        'description' => 'string',
        'frequency_type' => FrequencyTypeEnum::class,
    ];

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'account_user')
            ->using(AccountUser::class) // Specify the pivot model
            ->withPivot([
                'amount_due',
            ]);
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
                                ->label('Select No for a Custom Account')
                                ->boolean()
                                ->default(true)
                                ->inline()
                                ->grouped()
                                ->reactive()
                                ->columnSpanFull(),
                        ]),
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
                    Fieldset::make('Billing Account Type')
                        ->schema([
                            ToggleButtons::make('billing_type')
                                ->boolean()
                                ->label('Select No for a Credit Account')
                                ->default(true)
                                ->inline()
                                ->grouped()
                                ->reactive()
                                ->columnSpanFull(),
                        ]),
                    Fieldset::make('Cost Overview')
                        ->schema([
                            TextInput::make('amount_due')
                                ->label('Amount Due Per Member')
                                ->required()
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
                        ->visible(fn ($state) => $state['is_general']),
                    Fieldset::make('Custom Account Details')
                        ->schema([
                            Repeater::make('users')
                                ->visible(fn () => true)
                                ->schema([
                                    Select::make('user_id')
                                        ->label('Member')
                                        ->options(User::query()->pluck('name', 'id')) // Fetch users manually
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(1),
                                    TextInput::make('amount_due')
                                        ->label('Amount Due Per Member')
                                        ->required()
                                        ->numeric()
                                        ->hintIcon('heroicon-o-currency-dollar')
                                        ->prefix('KES')
                                        ->reactive()
                                        ->debounce(500)
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            // Set budget to the same value as amount_due directly for custom accounts
                                            $set('budget', $state);
                                        }),
                                    TextInput::make('budget')
                                        ->label('Estimated Group Budget')
                                        ->numeric()
                                        ->hintIcon('heroicon-o-currency-dollar')
                                        ->prefix('KES')
                                        ->disabled(), // Changed from `disabled()` to `readonly()`
                                ])
                                ->columns(3)
                                ->createItemButtonLabel('Add More Details')
                                ->columnSpanFull(),
                        ])
                        ->visible(fn ($state) => !$state['is_general']),
                ]),
        ];
    }
}
