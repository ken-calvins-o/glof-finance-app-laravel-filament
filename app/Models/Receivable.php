<?php

namespace App\Models;

use App\Enums\PaymentMode;
use Awcodes\Shout\Components\Shout;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Receivable extends Model
{
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'account_id' => 'integer',
        'from_savings' => 'boolean',
        'payment_mode' => PaymentMode::class,
    ];

    public function getMonthIdAttribute()
    {
        return $this->months()->pluck('months.id')->first(); // Explicitly specify 'months.id'
    }

    public function getYearIdAttribute()
    {
        return $this->years()->pluck('years.id')->first(); // Explicitly specify 'years.id'
    }

    public function months(): BelongsToMany
    {
        return $this->belongsToMany(Month::class, 'monthly_receivable')
            ->using(MonthlyReceivable::class)
            ->withPivot(['month_id']);
    }

    public function years(): BelongsToMany
    {
        return $this->belongsToMany(Year::class, 'receivable_year')
            ->using(ReceivableYear::class)
            ->withPivot(['year_id']);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function debt(): HasOne
    {
        return $this->hasOne(Debt::class);
    }

    public static function getForm(): array
    {
        return [
            Repeater::make('Members Receivable')
                ->schema([
                    Fieldset::make('Receivable Details')
                        ->schema([
                            // Select the Member/User
                            Select::make('user_id')
                                ->label('Member')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn() => null), // Do nothing on state update for user_id.

                            // Payment Mode Selection
                            Select::make('payment_mode')
                                ->enum(PaymentMode::class)
                                ->hintIcon('heroicon-o-banknotes')
                                ->default(PaymentMode::Bank_Transfer)
                                ->options(
                                    collect(PaymentMode::cases())
                                        ->reject(fn($case) => $case === PaymentMode::Credit_Loan) // Exclude the 'Credit_Loan' enum case
                                        ->mapWithKeys(fn($case) => [$case->value => ucwords(str_replace('_', ' ', $case->value))])
                                )
                                ->searchable()
                                ->required(),

                            // Select Account
                            Select::make('account_id')
                                ->relationship('account', 'name')
                                ->label('Account')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn() => null), // Do nothing on state update for account_id.

                            // Amount Field Without Auto-Updating
                            TextInput::make('amount_contributed')
                                ->label('Amount')
                                ->required()
                                ->lazy() // Will only be updated when the user explicitly enters a value.
                                ->live()
                                ->numeric()
                                ->hintIcon('heroicon-o-currency-dollar')
                                ->prefix('Kes')
                                ->helperText(function (callable $get) {
                                    $accountId = $get('account_id'); // Get the selected account_id
                                    $userId = $get('user_id'); // Get the selected user_id

                                    // Fetch amount_contributed using reusable method
                                    $contributedAmount = self::getContributedAmount($accountId, $userId);

                                    // Format the amount using number_format to include commas and 2 decimal places
                                    $contributedAmountFormatted = number_format($contributedAmount ?? 0, 2);

                                    // Show existing amount as guidance, but do not populate the field.
                                    return $contributedAmount
                                        ? "Kes $contributedAmountFormatted has been contributed to this account."
                                        : "Kes " . number_format(0, 2); // Ensure fallback is also properly formatted
                                }),
                        ])
                        ->columns(2),

                    Fieldset::make('Accounting Period')
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
                        ]),

                    // Payment Mode Configuration
                    Fieldset::make('Payment Mode')
                        ->schema([
                            ToggleButtons::make('from_savings')
                                ->label('Do you want to deduct from the member\'s savings account?')
                                ->boolean() // Treat as a boolean
                                ->default(false) // Default to false
                                ->inline()
                                ->grouped()
                                ->reactive()
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * Helper method to fetch the amount_contributed from `account_collections`.
     *
     * @param int|null $accountId
     * @param int|null $userId
     * @return float|null
     */
    private static function getContributedAmount(?int $accountId, ?int $userId): ?float
    {
        if (!$accountId || !$userId) {
            return null;
        }

        return DB::table('account_collections')
            ->where('account_id', $accountId)
            ->where('user_id', $userId)
            ->value('amount'); // Fetch the amount field
    }
}
