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
use Illuminate\Database\Eloquent\Relations\HasOne;

class Receivable extends Model
{
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'account_id' => 'integer',
        'from_savings' => 'boolean',
        'payment_mode' => PaymentMode::class,
    ];

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
//            Shout::make('contribution_amount')
//                ->type('warning')
//                ->visible(fn(callable $get) => $get('outstanding_balance') < $get('amount_contributed'))
//                ->content('The entered amount exceeds the outstanding balance 😬!')
//                ->columnSpanFull(),
            Repeater::make('Members Receivable')
                ->schema([
                    Fieldset::make('Receivable Details')
                        ->schema([
                            Select::make('user_id')
                                ->label('Member')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn(callable $set, callable $get) => self::updateBalance($set, $get)),
                            Select::make('payment_mode')
                                ->enum(PaymentMode::class)
                                ->hintIcon('heroicon-o-banknotes')
                                ->default(PaymentMode::Bank_Transfer)
                                ->options(collect(PaymentMode::cases())->mapWithKeys(function ($case) {
                                    return [$case->value => ucwords(str_replace('_', ' ', $case->value))];
                                }))
                                ->searchable()
                                ->required(),
                            Select::make('account_id')
                                ->label('Account')
                                ->options(fn(callable $get) => self::fetchAccountOptions($get('user_id')))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn(callable $set, callable $get) => self::updateBalance($set, $get)),
                            TextInput::make('amount_contributed')
                                ->label('Amount')
                                ->required()
                                ->lazy()
                                ->live()
                                ->minValue(1)
                                ->numeric()
                                ->hintIcon('heroicon-o-currency-dollar')
                                ->prefix('KES')
                                ->helperText(fn(callable $get) => self::generateHelperText($get)),

                        ])
                        ->columns(2),
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

    private static function fetchAccountOptions($userId)
    {
        return $userId ? AccountUser::where('user_id', $userId)
            ->with('account:id,name')
            ->get()
            ->mapWithKeys(fn($accountUser) => [$accountUser->account->id => $accountUser->account->name])
            ->toArray() : [];
    }

    private static function generateHelperText(callable $get)
    {
        $balance = $get('outstanding_balance');
        return 'Outstanding Balance: ' . ($balance ?: 'N/A');
    }

    protected static function updateBalance(callable $set, callable $get)
    {
        $userId = $get('user_id');
        $accountId = $get('account_id');

        if ($userId && $accountId) {
            // Fetch the outstanding_balance from the Debt model
            $balance = Debt::where('user_id', $userId)
                ->where('account_id', $accountId)
                ->value('outstanding_balance');

            $set('outstanding_balance', $balance ?? 'N/A');
        } else {
            $set('outstanding_balance', 'N/A');
        }
    }
}
