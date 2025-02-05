<?php

namespace App\Models;

use App\Enums\DebtStatusEnum;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debt extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'account_id' => 'integer',
        'outstanding_balance' => 'decimal:2',
        'repayment_amount' => 'decimal:2',
        'from_savings' => 'boolean',
        'debt_status' => DebtStatusEnum::class,
    ];

    // Define relationships
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Bulk update for debts
    public static function bulkUpdateDebtStatus($debtIds)
    {
        $debts = self::whereIn('id', $debtIds)->get();

        foreach ($debts as $debt) {
            $debt->update(['debt_status' => $debt->outstanding_balance <= 0 ? DebtStatusEnum::Cleared : DebtStatusEnum::Pending]);
        }
    }

    public static function getForm(): array
    {
        return [
            Section::make('Debt Repayment')
                ->icon('heroicon-s-pencil-square')
                ->columns(['md' => 2, 'lg' => 2])
                ->schema([
                    Fieldset::make('Member & Account Information')->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Contributor')
                            ->disabled()
                            ->required(),
                        Select::make('account_id')
                            ->relationship('account', 'name')
                            ->label('Account Name')
                            ->disabled(),
                    ]),
                    Fieldset::make('Debt Overview')
                        ->schema([
                            TextInput::make('outstanding_balance')
                                ->label('Outstanding balance')
                                ->required()
                                ->numeric()
                                ->disabled()
                                ->hintIcon('heroicon-o-currency-dollar')
                                ->prefix('Kes')
                                ->minValue(1),
                            TextInput::make('repayment_amount')
                                ->label('Enter Amount')
                                ->required()
                                ->numeric()
                                ->hintIcon('heroicon-o-currency-dollar')
                                ->prefix('Kes')
                                ->minValue(1),
                        ]),

                    Fieldset::make('Payment Mode')
                        ->schema([
                            ToggleButtons::make('from_savings')
                                ->boolean()
                                ->label('Do you want to repay from the member\'s savings account?')
                                ->default(false)
                                ->inline()
                                ->grouped()
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }
}
