<?php

namespace App\Models;

use App\Enums\DebtStatusEnum;
use App\Enums\PaymentMode;
use App\Enums\PaymentStatusEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contribution extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'account_id' => 'integer',
        'amount' => 'decimal:2',
        'payment_method' => PaymentMode::class,
        'payment_status' => PaymentStatusEnum::class,
    ];

    protected static function booted()
    {
        static::created(function ($contribution) {
            // Handle deductions for Personal_Savings payment method
            if ($contribution->payment_method === PaymentMode::Personal_Savings) {
                $saving = Saving::where('user_id', $contribution->user_id)->first();

                if ($saving && $saving->amount >= $contribution->amount) {
                    // Deduct contribution from savings
                    $saving->amount -= $contribution->amount;

                    // Add contribution back to net worth
                    $saving->net_worth += $contribution->amount;

                    $saving->save();
                } else {
                    throw new \Exception('Insufficient savings to make this contribution.');
                }
            }

            // Handle contributions for other payment modes except Group_Credit
            if ($contribution->payment_method !== PaymentMode::Group_Credit) {
                $saving = Saving::where('user_id', $contribution->user_id)->first();

                if ($saving) {
                    // Add the contribution amount to the net_worth
                    $saving->net_worth += $contribution->amount;

                    $saving->save();
                }
            }

            // Handle debt balance updates (Separate from payment status)
            $debt = Debt::where('account_id', $contribution->account_id)
                ->where('user_id', $contribution->user_id)
                ->first();

            $interest = 0;

            if ($debt) {
                $originalDebtBalance = $debt->balance;

                if ($contribution->payment_method === PaymentMode::Group_Credit) {
                    // Calculate interest for Group_Credit payment method
                    if ($contribution->amount == $originalDebtBalance) {
                        $interest = $contribution->amount * 0.01; // 1% interest
                        $debt->balance += $interest;
                    } elseif ($contribution->amount < $originalDebtBalance) {
                        $interest = $contribution->amount * 0.01;
                        $debt->balance -= $contribution->amount;
                        $debt->balance += $interest;
                    }
                } else {
                    $debt->balance -= $contribution->amount; // Non-Group_Credit payments
                }

                $debt->balance = max($debt->balance, 0); // Ensure balance doesn't go negative

                $debt->debt_status = $debt->balance > 0
                    ? DebtStatusEnum::Pending
                    : DebtStatusEnum::Cleared;

                $debt->save();
            }

            // Handle contribution payment status updates
            if ($contribution->payment_method === PaymentMode::Group_Credit) {
                // Always set payment status to Partially_Credited for Group_Credit
                $contribution->payment_status = PaymentStatusEnum::Credited;
            } else {
                // Calculate total amount contributed for this user and account
                $totalContributed = Contribution::where('user_id', $contribution->user_id)
                    ->where('account_id', $contribution->account_id)
                    ->sum('amount');

                // Get the expected amount from the Account model
                $expectedAmount = $contribution->account->amount;

                // Compare the total contribution to the expected amount
                if ($totalContributed >= $expectedAmount) {
                    // Payment is complete if the total contribution is greater than or equal to the expected amount
                    $contribution->payment_status = PaymentStatusEnum::Completed;
                } elseif ($totalContributed > 0 && $totalContributed < $expectedAmount) {
                    // Payment is partially paid if the total contribution is less than the expected amount
                    $contribution->payment_status = PaymentStatusEnum::Partially_Paid;
                }
            }

            // Update account balance
            $account = $contribution->account;
            if ($account) {
                $account->balance = $account->calculateBalance();
                $account->save();
            }

            // Record interest in Income table if applicable
            if ($contribution->payment_method === PaymentMode::Group_Credit && $interest > 0) {
                Income::create([
                    'user_id' => $contribution->user_id,
                    'account_id' => $contribution->account_id,
                    'interest_amount' => $interest,
                    'description' => 'Interest generated from group credit contribution',
                ]);
            }

            // Deduct only the interest amount from net worth for Group_Credit payment method
            if ($contribution->payment_method === PaymentMode::Group_Credit) {
                $saving = Saving::where('user_id', $contribution->user_id)->first();

                if ($saving && $interest > 0) {
                    $saving->net_worth -= $interest; // Deduct only interest from net worth
                    $saving->save();
                }
            }

            // Save the updated contribution status
            $contribution->save();
        });
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generalAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public static function getForm(): array
    {
        return [
            Section::make('Contribution Details')
                ->icon('heroicon-s-pencil-square')
                ->columns(['md' => 2, 'lg' => 2])
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label('Contributor')
                        ->searchable()
                        ->preload()
                        ->editOptionForm(User::getForm())
                        ->createOptionForm(User::getForm())
                        ->required()
                        ->reactive(),

                    Select::make('account_id')
                        ->relationship('account', 'name')
                        ->label('Account Name')
                        ->searchable()
                        ->preload()
                        ->editOptionForm(Account::getForm())
                        ->createOptionForm(Account::getForm())
                        ->required()
                        ->reactive(),

                    TextInput::make('amount')
                        ->label('Amount')
                        ->required()
                        ->numeric()
                        ->hintIcon('heroicon-o-currency-dollar')
                        ->prefix('Kes')
                        ->minValue(1)
                        ->helperText(function (callable $get) {
                            $userId = $get('user_id');
                            $accountId = $get('account_id');

                            if ($userId && $accountId) {
                                // Fetch the debt balance for the specified user and account
                                $debt = Debt::where('user_id', $userId)
                                    ->where('account_id', $accountId)
                                    ->first();

                                return $debt
                                    ? "Outstanding balance for this account: Kes " . number_format($debt->balance, 2)
                                    : "No outstanding balance for this account.";
                            }

                            return 'Pick a contributor and account to see amount info.';
                        })
                        ->disabled(function (callable $get) {
                            // Disable the field until both user_id and account_id are selected
                            return !$get('user_id') || !$get('account_id');
                        })
                        ->reactive(),
                    Select::make('payment_method')
                        ->label('Payment mode')
                        ->enum(PaymentMode::class)
                        ->hintIcon('heroicon-o-banknotes')
                        ->default(PaymentMode::Mobile_Money)
                        ->options(collect(PaymentMode::cases())->mapWithKeys(function ($case) {
                            return [$case->value => ucwords(str_replace('_', ' ', $case->value))];
                        }))
                        ->searchable()
                        ->required(),
                ]),

            Textarea::make('description')
                ->label('Description of the contribution')
                ->maxLength(255)
                ->columnSpan('full'),
        ];
    }
}
