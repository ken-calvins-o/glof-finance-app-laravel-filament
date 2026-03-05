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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\ReceivableEffectService;

class Receivable extends Model
{
    use SoftDeletes; // enable soft deletes so we can safely revert on delete and support restore

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

    public function effects(): HasMany
    {
        return $this->hasMany(ReceivableEffect::class);
    }

    public static function getForm(): array
    {
        $updateContributionHint = function (callable $get, callable $set): void {
            $accountId = $get('account_id');
            $userId = $get('user_id');

            if (! $accountId || ! $userId) {
                $set('contributed_amount_hint', null);
                return;
            }

            $amount = AccountCollection::query()
                ->where('account_id', (int) $accountId)
                ->where('user_id', (int) $userId)
                ->value('amount');

            $amount = $amount !== null ? (float) $amount : 0.0;

            $set('contributed_amount_hint', 'Kes ' . number_format($amount, 2) . ' has been contributed to this account.');
        };

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
                                ->afterStateUpdated(function (callable $get, callable $set) use ($updateContributionHint): void {
                                    $updateContributionHint($get, $set);
                                }),

                            // Payment Mode Selection
                            Select::make('payment_mode')
                                ->enum(PaymentMode::class)
                                ->hintIcon('heroicon-o-banknotes')
                                ->default(PaymentMode::Bank_Transfer)
                                ->options(
                                    collect(PaymentMode::cases())
                                        // Exclude both 'From_Savings' and 'Credit_Loan' cases
                                        ->reject(fn($case) => $case === PaymentMode::From_Savings || $case === PaymentMode::Credit_Loan)
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
                                ->afterStateUpdated(function (callable $get, callable $set) use ($updateContributionHint): void {
                                    $updateContributionHint($get, $set);
                                }),

                            // Amount Field Without Auto-Updating
                            TextInput::make('amount_contributed')
                                ->label('Amount')
                                ->required()
                                ->lazy() // Will only be updated when the user explicitly enters a value.
                                ->numeric()
                                ->hintIcon('heroicon-o-currency-dollar')
                                ->prefix('Kes')
                                ->helperText(fn (callable $get) => $get('contributed_amount_hint')),
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
                        ])
                ])
                ->columnSpanFull(),
        ];
    }


    /**
     * Boot model events to capture effects and revert on delete/restore.
     */
    protected static function booted()
    {
        // After creating a receivable, record the system effects so we can revert later.
        static::created(function (Receivable $receivable) {
            // Delegates to service which inspects DB and saves an effect snapshot
            try {
                // Avoid double-recording: if an effect was already created (e.g. in CreateReceivable), skip
                if (class_exists(\App\Models\ReceivableEffect::class)) {
                    $exists = \App\Models\ReceivableEffect::where('receivable_id', $receivable->id)->exists();
                    if ($exists) {
                        return;
                    }
                }

                (new ReceivableEffectService())->recordCreationEffects($receivable);
            } catch (\Throwable $e) {
                // Don't interrupt creation, but report to the error log for investigation
                report($e);
            }
        });

        // When deleting (soft-delete) a receivable, revert its effects atomically.
        static::deleting(function (Receivable $receivable) {
            // Only act on soft-deletes (not forceDelete)
            if ($receivable->isForceDeleting()) {
                return;
            }

            try {
                (new ReceivableEffectService())->revertEffectsForReceivable($receivable);
            } catch (\Throwable $e) {
                report($e);
                // throw to prevent deletion if revert fails
                throw $e;
            }
        });

        // On restore, replay the reversal (i.e., restore previous effects)
        static::restored(function (Receivable $receivable) {
            try {
                (new ReceivableEffectService())->restoreEffectsForReceivable($receivable);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
