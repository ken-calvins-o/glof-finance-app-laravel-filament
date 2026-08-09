<?php

namespace App\Models;

use App\Enums\DebtStatusEnum;
use App\Support\Money;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;

class Loan extends Model
{
    use HasFactory;

    /**
     * The group's standard monthly rate, and the same rate the scheduled
     * interest command applies to outstanding debts.
     */
    public const DEFAULT_MONTHLY_RATE = 1.0;

    protected $casts = [
        'id' => 'integer',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        // Stored as the monthly rate, e.g. "1" meaning 1% per month.
        'interest' => 'string',
        'apply_interest' => 'boolean',
        'due_date' => 'datetime',
        'debt_status' => DebtStatusEnum::class,
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * What the loan costs the member, in shillings.
     */
    public static function interestAmount(float $amount, float $rate, bool $applyInterest): float
    {
        return $applyInterest ? round($amount * ($rate / 100), 2) : 0.0;
    }

    public static function repayableAmount(float $amount, float $rate, bool $applyInterest): float
    {
        return round($amount + static::interestAmount($amount, $rate, $applyInterest), 2);
    }

    /**
     * Issuing a loan.
     *
     * Problems this form had:
     *
     *  - Typing an amount always set the balance to amount + 1%, whether or not
     *    interest had been switched on, and the field showing that balance was
     *    hidden unless interest was on. So the number the treasurer saw was
     *    frequently not the number that would be saved.
     *  - "Balance (Amount + 1% Interest)" and "Percentage per month" were both
     *    read-only, which raised the obvious question of why they were inputs.
     *  - The due date used a date *and time* picker. No loan is due at 14:35.
     *  - The status dropdown offered "Cleared" and "Defaulted" on a loan that
     *    did not exist yet.
     *
     * The replacement asks only what a human decides — who, how much, is there
     * interest, when is it due — and shows the arithmetic as a plain sentence
     * rather than as two disabled boxes.
     */
    public static function getForm(): array
    {
        return [
            Section::make('The loan')
                ->description('Who is borrowing, and how much.')
                ->icon('heroicon-o-hand-raised')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->label('Member')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required()
                        ->createOptionForm(User::getForm())
                        ->live()
                        // Lending to someone who already owes money is a
                        // decision, not an accident — so say so at the moment
                        // it is being made.
                        ->helperText(function ($state): ?string {
                            if (! $state) {
                                return null;
                            }

                            $owing = User::find($state)?->outstanding_debt ?? 0;

                            return $owing > 0
                                ? '⚠ This member already owes ' . Money::kes($owing) . '.'
                                : 'This member has no outstanding debt.';
                        }),

                    TextInput::make('amount')
                        ->label('Amount borrowed')
                        ->helperText('What the member actually receives.')
                        ->prefix('KES')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->live(onBlur: true),

                    Textarea::make('description')
                        ->label('What is it for?')
                        ->placeholder('e.g. School fees for the January term')
                        ->helperText('Optional, but it makes the loan register far easier to read later.')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Interest')
                ->description('Whether the group charges for this loan.')
                ->icon('heroicon-o-percent-badge')
                ->columns(2)
                ->schema([
                    ToggleButtons::make('apply_interest')
                        ->label('Charge interest on this loan?')
                        ->boolean('Yes, charge interest', 'No, interest free')
                        ->default(false)
                        ->required()
                        ->inline()
                        ->grouped()
                        ->live()
                        ->columnSpanFull(),

                    TextInput::make('interest')
                        ->label('Monthly rate')
                        ->suffix('% per month')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(self::DEFAULT_MONTHLY_RATE)
                        ->required(fn (Get $get) => (bool) $get('apply_interest'))
                        ->helperText('The group standard is ' . self::DEFAULT_MONTHLY_RATE . '% a month. Change it only if this loan was agreed on different terms.')
                        ->live(onBlur: true)
                        ->visible(fn (Get $get) => (bool) $get('apply_interest'))
                        ->columnSpanFull(),
                ]),

            Section::make('Repayment')
                ->description('When it falls due, and where the loan stands.')
                ->icon('heroicon-o-calendar')
                ->columns(2)
                ->schema([
                    DatePicker::make('due_date')
                        ->label('Due date')
                        ->native(false)
                        ->displayFormat('j M Y')
                        ->minDate(fn (string $context) => $context === 'create' ? today() : null)
                        ->default(fn () => today()->addMonths(6))
                        ->required(),

                    Select::make('debt_status')
                        ->label('Status')
                        ->native(false)
                        ->default(DebtStatusEnum::Pending->value)
                        ->required()
                        // A loan being created cannot already be repaid or
                        // written off, so those options are not offered.
                        ->options(fn (string $context) => $context === 'create'
                            ? collect([DebtStatusEnum::Pending, DebtStatusEnum::Approved, DebtStatusEnum::Rejected])
                                ->mapWithKeys(fn (DebtStatusEnum $case) => [$case->value => $case->getLabel()])
                                ->all()
                            : DebtStatusEnum::options()),

                    /*
                    | The whole loan in one sentence. This is the thing the
                    | treasurer reads back to the member before agreeing, and
                    | previously it existed nowhere on the screen.
                    */
                    Placeholder::make('loan_summary')
                        ->label('In plain terms')
                        ->content(fn (Get $get) => static::summarise($get))
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected static function summarise(Get $get): HtmlString
    {
        $amount = (float) ($get('amount') ?? 0);

        if ($amount <= 0) {
            return new HtmlString('<span class="text-gray-500">Enter an amount to see what this loan will cost.</span>');
        }

        $applyInterest = (bool) $get('apply_interest');
        $rate = (float) ($get('interest') ?? self::DEFAULT_MONTHLY_RATE);
        $interest = static::interestAmount($amount, $rate, $applyInterest);
        $repayable = static::repayableAmount($amount, $rate, $applyInterest);

        $name = $get('user_id') ? (User::find($get('user_id'))?->name ?? 'The member') : 'The member';
        $due = $get('due_date') ? \Illuminate\Support\Carbon::parse($get('due_date'))->format('j M Y') : 'the due date';

        return new HtmlString(sprintf(
            '<div class="space-y-1">
                <p class="text-sm text-gray-950 dark:text-white"><strong>%s</strong> receives <strong>%s</strong>.</p>
                <p class="text-sm text-gray-950 dark:text-white">They repay <strong>%s</strong> by <strong>%s</strong>%s.</p>
                <p class="text-xs text-gray-500">Interest keeps accruing at %s%%%s a month on anything still owed after that date.</p>
             </div>',
            e($name),
            e(Money::kes($amount)),
            e(Money::kes($repayable)),
            e($due),
            $interest > 0 ? ', which includes ' . e(Money::kes($interest)) . ' interest' : ', with no interest',
            e((string) self::DEFAULT_MONTHLY_RATE),
            '',
        ));
    }
}
