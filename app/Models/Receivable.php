<?php

namespace App\Models;

use App\Enums\PaymentMode;
use App\Support\Money;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\ReceivableEffectService;

class Receivable extends Model
{
    use SoftDeletes; // enable soft deletes so we can safely revert on delete and support restore

    /*
    | The cast used to be declared against `payment_mode`, which is not a column
    | on this table — the column is `payment_method`. So the cast did nothing,
    | every payment method came back as a bare string, and the table's badge
    | could not colour or label it.
    |
    | It is resolved through PaymentMode::tryFrom() at the point of display
    | rather than cast here, so that a row carrying a value written before the
    | enum settled down degrades to plain text instead of throwing and taking
    | the whole table with it.
    */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'account_id' => 'integer',
        'from_savings' => 'boolean',
    ];

    /**
     * The payment method as an enum, or null if the stored value predates it.
     */
    public function getPaymentModeAttribute(): ?PaymentMode
    {
        return PaymentMode::tryFrom((string) $this->payment_method);
    }

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

    /**
     * The entry type chosen at the top of the form. "Arrears" is stored as a
     * negative amount, which is what the creation service already understands.
     */
    public const ENTRY_PAYMENT = 'payment';

    public const ENTRY_ARREARS = 'arrears';

    /**
     * Written when a collection is the settlement of a debt rather than an
     * ordinary contribution. See the migration that added the column.
     */
    public const SOURCE_DEBT_REPAYMENT = 'debt_repayment';

    public function isDebtRepayment(): bool
    {
        return $this->source === self::SOURCE_DEBT_REPAYMENT;
    }

    /**
     * How much a member has already put into a given fund.
     */
    public static function contributedSoFar(?int $userId, ?int $accountId): ?float
    {
        if (! $userId || ! $accountId) {
            return null;
        }

        return (float) (AccountCollection::query()
            ->where('user_id', $userId)
            ->where('account_id', $accountId)
            ->value('amount') ?? 0.0);
    }

    /**
     * Recording money coming in.
     *
     * What changed, and why:
     *
     * 1. The period (month and year) was asked once per member row. Posting a
     *    meeting's twenty contributions meant setting the same month twenty
     *    times, and any slip silently filed one member's money in the wrong
     *    month. It is now asked once, at the top, and applied to every row.
     *
     * 2. There were two separate controls both labelled "Payment Mode": a
     *    dropdown of payment methods, and a yes/no toggle asking whether to
     *    deduct from the member's savings. They are one question — where did
     *    this money come from — so they are now one dropdown.
     *
     * 3. Entering a negative amount quietly created a debt. Nothing on screen
     *    said so. That behaviour is preserved but is now reached by choosing
     *    "Arrears" deliberately at the top of the form, rather than by typing a
     *    minus sign and hoping.
     *
     * 4. Each row was wrapped in three nested boxes (Repeater > Fieldset >
     *    Fieldset) around four fields. Rows are now a single line of inputs,
     *    so twenty of them fit on a screen instead of three.
     */
    public static function getForm(): array
    {
        return [
            Section::make('What are you recording?')
                ->description('These settings apply to every entry you add below.')
                ->icon('heroicon-o-calendar-days')
                ->columns(3)
                ->schema([
                    ToggleButtons::make('entry_type')
                        ->label('Type of entry')
                        ->options([
                            self::ENTRY_PAYMENT => 'Money received',
                            self::ENTRY_ARREARS => 'Arrears owed',
                        ])
                        ->icons([
                            self::ENTRY_PAYMENT => 'heroicon-o-arrow-down-tray',
                            self::ENTRY_ARREARS => 'heroicon-o-exclamation-triangle',
                        ])
                        ->colors([
                            self::ENTRY_PAYMENT => 'success',
                            self::ENTRY_ARREARS => 'danger',
                        ])
                        ->default(self::ENTRY_PAYMENT)
                        ->required()
                        ->inline()
                        ->grouped()
                        ->live()
                        ->helperText(fn ($state) => $state === self::ENTRY_ARREARS
                            ? 'Records what these members still owe. It creates a debt against them instead of crediting a payment.'
                            : 'Records money the group has actually received.')
                        ->columnSpanFull(),

                    Select::make('month_id')
                        ->label('Month it belongs to')
                        ->options(fn () => Month::orderBy('id')->pluck('name', 'id')->all())
                        ->default(fn () => Month::where('name', now()->format('F'))->value('id'))
                        ->native(false)
                        ->searchable()
                        ->required(),

                    Select::make('year_id')
                        ->label('Year')
                        ->options(fn () => Year::orderByDesc('year')->pluck('year', 'id')->all())
                        ->default(fn () => Year::where('year', now()->year)->value('id'))
                        ->native(false)
                        ->searchable()
                        ->required(),

                    Placeholder::make('period_note')
                        ->hiddenLabel()
                        ->content(new HtmlString(
                            'This is the month the money is <em>for</em>, which is not always the month you are entering it in.'
                        )),
                ]),

            Section::make('Entries')
                ->description('Add one row per member. You can add as many as you need before saving.')
                ->icon('heroicon-o-list-bullet')
                ->schema([
                    Repeater::make('entries')
                        ->hiddenLabel()
                        ->addActionLabel('Add another member')
                        ->reorderable(false)
                        ->cloneable()
                        ->defaultItems(1)
                        ->minItems(1)
                        ->live()
                        ->itemLabel(fn (array $state): ?string => self::describeRow($state))
                        ->columns(12)
                        ->schema([
                            Select::make('user_id')
                                ->label('Member')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->columnSpan(['default' => 12, 'md' => 3]),

                            Select::make('account_id')
                                ->label('Fund')
                                ->relationship('account', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->columnSpan(['default' => 12, 'md' => 3]),

                            TextInput::make('amount_contributed')
                                ->label('Amount')
                                ->prefix('KES')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->validationMessages([
                                    'min' => 'Enter a positive amount. To record what a member owes, switch the type to "Arrears owed" at the top.',
                                ])
                                ->live(onBlur: true)
                                // Answers "have they already paid this one?" right
                                // where the treasurer is about to type the figure.
                                ->helperText(function (Get $get): ?string {
                                    $paid = self::contributedSoFar(
                                        $get('user_id') ? (int) $get('user_id') : null,
                                        $get('account_id') ? (int) $get('account_id') : null,
                                    );

                                    return $paid === null
                                        ? 'Pick a member and a fund to see what they have paid so far.'
                                        : 'Paid into this fund so far: ' . Money::kes($paid);
                                })
                                ->columnSpan(['default' => 12, 'md' => 3]),

                            Select::make('payment_source')
                                ->label('Paid by')
                                ->options(fn () => PaymentMode::externalOptions() + [
                                    PaymentMode::From_Savings->value => PaymentMode::From_Savings->getLabel(),
                                ])
                                ->default(PaymentMode::Mobile_Money->value)
                                ->native(false)
                                ->searchable()
                                ->required()
                                ->helperText(fn ($state) => $state === PaymentMode::From_Savings->value
                                    ? 'Deducted from savings they already hold with the group.'
                                    : null)
                                // Arrears are, by definition, money that has not
                                // been paid by any means — so the question is hidden.
                                ->visible(fn (Get $get) => $get('../../entry_type') !== self::ENTRY_ARREARS)
                                ->columnSpan(['default' => 12, 'md' => 3]),
                        ])
                        ->columnSpanFull(),

                    // The batch total. Posting a meeting's collections against a
                    // cash tin or an M-PESA statement means checking one number,
                    // and there was previously nowhere to check it.
                    Placeholder::make('batch_total')
                        ->label('Total for this batch')
                        ->content(function (Get $get): HtmlString {
                            $entries = collect($get('entries') ?? []);

                            $total = $entries->sum(fn ($row) => (float) ($row['amount_contributed'] ?? 0));
                            $count = $entries->filter(fn ($row) => filled($row['user_id'] ?? null))->count();
                            $isArrears = $get('entry_type') === self::ENTRY_ARREARS;

                            return new HtmlString(sprintf(
                                '<span class="text-lg font-semibold %s">%s</span>
                                 <span class="text-sm text-gray-500"> across %d %s%s</span>',
                                $isArrears ? 'text-danger-600' : 'text-success-600',
                                e(Money::kes($total)),
                                $count,
                                $count === 1 ? 'entry' : 'entries',
                                $isArrears ? ', recorded as money owed' : '',
                            ));
                        }),
                ]),
        ];
    }

    /**
     * A one-line summary for a collapsed repeater row, so a long batch stays
     * readable without expanding every row.
     */
    protected static function describeRow(array $state): ?string
    {
        $name = $state['user_id']
            ? User::find($state['user_id'])?->name
            : null;

        if (! $name) {
            return null;
        }

        $fund = $state['account_id'] ? Account::find($state['account_id'])?->name : null;
        $amount = $state['amount_contributed'] ?? null;

        return trim(sprintf(
            '%s%s%s',
            $name,
            $fund ? ' — ' . $fund : '',
            is_numeric($amount) ? ' · ' . Money::kes($amount) : '',
        ));
    }


    /**
     * Boot model events to capture effects and revert on delete/restore.
     */
    protected static function booted()
    {
        // After creating a receivable, record the system effects so we can revert later.
        static::created(function (Receivable $receivable) {
            /*
             * A repayment-sourced collection is written by DebtRepaymentService
             * after it has already adjusted the debt, the fund total and the
             * savings ledger itself. Recording reversal effects for it would
             * describe changes this row did not make, so it is skipped — and the
             * Collections table hides "Reverse" on these rows for the same
             * reason.
             */
            if ($receivable->isDebtRepayment()) {
                return;
            }

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
