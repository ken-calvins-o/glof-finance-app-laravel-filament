<?php

namespace App\Models;

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

                            if ($userId) {
                                // Fetch the current net worth dynamically once when user changes
                                $currentNetWorth = Saving::where('user_id', $userId)
                                    ->latest('id')
                                    ->value('net_worth') ?? 0;

                                // Set the current net worth
                                $set('current_net_worth', $currentNetWorth);

                                // Recalculate dependent fields
                                $creditAmount = $get('credit_amount') ?? 0;

                                // Prevent excessive updates
                                $set('net_worth', $currentNetWorth + $creditAmount);
                                $set('balance', $creditAmount); // balance mirrors credit_amount
                            } else {
                                // Reset fields if no member is selected
                                $set('current_net_worth', 0);
                                $set('net_worth', 0);
                                $set('balance', 0);
                            }
                        }),

                    // Credit Amount Field
                    TextInput::make('credit_amount')
                        ->label('Amount')
                        ->required()
                        ->numeric()
                        ->hintIcon('heroicon-o-currency-dollar')
                        ->prefix('Kes')
                        ->reactive()
                        ->debounce(300) // Add debounce to prevent recalculations on every keystroke
                        ->afterStateUpdated(function (callable $get, callable $set) {
                            // Efficiently update dependent fields when credit amount changes
                            $creditAmount = $get('credit_amount') ?? 0;
                            $currentNetWorth = $get('current_net_worth') ?? 0;

                            // Prevent excessive updates on every keystroke
                            $set('net_worth', $currentNetWorth + $creditAmount);
                            $set('balance', $creditAmount); // balance mirrors credit_amount
                        }),

                    // Read-Only New Net Worth Field
                    TextInput::make('net_worth')
                        ->label('Expected New Net Worth')
                        ->prefix('Kes')
                        ->readOnly() // Use readOnly instead of disabled (to allow styling)
                        ->default(0) // Default value if no calculations
                        ->reactive() // Ensure it updates dynamically when inputs change
                        ->debounce(300), // Ensure smooth updates with debounce

                    // Read-Only Balance Field
                    TextInput::make('balance')
                        ->label('Estimated Savings')
                        ->prefix('Kes')
                        ->readOnly() // Prevent manual editing of balance
                        ->default(0) // Default value initially
                        ->reactive() // Dynamically updates alongside credit_amount
                        ->debounce(300), // Ensure smooth updates with debounce

                    // Hidden Field to Track Current Net Worth
                    TextInput::make('current_net_worth')
                        ->hidden() // Keep it hidden from user input
                        ->default(0), // Default value
                ]),
        ];
    }
}
