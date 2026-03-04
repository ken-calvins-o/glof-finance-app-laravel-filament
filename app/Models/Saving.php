<?php

namespace App\Models;

use App\Services\SavingsCalculator;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Saving extends Model
{
    protected $fillable = ['user_id', 'credit_amount', 'debit_amount', 'balance', 'net_worth'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getForm(): array
    {
        return [
            Section::make('Savings Details')
                ->icon('heroicon-s-pencil-square')
                ->columns(['md' => 2, 'lg' => 2])
                ->schema([
                    // Member/Relationship Field
                    Select::make('user_id')
                        ->label('Member')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (callable $get, callable $set) {
                            $userId = $get('user_id');
                            $calculator = app(SavingsCalculator::class);

                            if ($userId) {
                                $creditAmount = floatval($get('credit_amount') ?? 0);
                                $defaults = $calculator->getFormDefaults($userId, $creditAmount);

                                foreach ($defaults as $field => $value) {
                                    $set($field, $value);
                                }
                            } else {
                                $resetValues = $calculator->getResetValues();

                                foreach ($resetValues as $field => $value) {
                                    $set($field, $value);
                                }
                            }
                        }),

                    // Credit Amount Field
                    TextInput::make('credit_amount')
                        ->label('Amount')
                        ->required()
                        ->numeric()
                        ->step('0.01')
                        ->hintIcon('heroicon-o-currency-dollar')
                        ->prefix('Kes')
                        ->reactive()
                        ->debounce(700)
                        ->afterStateUpdated(function (callable $get, callable $set) {
                            $userId = $get('user_id');

                            if ($userId) {
                                $calculator = app(SavingsCalculator::class);
                                $creditAmount = floatval($get('credit_amount') ?? 0);
                                $defaults = $calculator->getFormDefaults($userId, $creditAmount);

                                foreach ($defaults as $field => $value) {
                                    $set($field, $value);
                                }
                            }
                        }),

                    // Read-Only New Net Worth Field
                    TextInput::make('net_worth')
                        ->label('Expected New Net Worth')
                        ->prefix('Kes')
                        ->readOnly()
                        ->default(0)
                        ->reactive(),

                    // Read-Only Balance Field
                    TextInput::make('balance')
                        ->label('Estimated Savings')
                        ->prefix('Kes')
                        ->readOnly()
                        ->default(0)
                        ->reactive(),

                    // Hidden Field to Track Current Net Worth
                    TextInput::make('current_net_worth')
                        ->hidden()
                        ->default(0),

                    // Hidden Field to Track Current Balance
                    TextInput::make('current_balance')
                        ->hidden()
                        ->default(0),
                ]),
        ];
    }
}
