<?php

namespace App\Models;

use App\Enums\PaymentMode;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Saving extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'credit_amount' => 'decimal:2',
        'debit_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'user_id' => 'integer',
        'net_worth' => 'decimal:2',
        'payment_date' => 'date',
        'payment_mode' => 'integer',
        'payment_method' => PaymentMode::class,
    ];

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
                    Select::make('user_id')
                        ->label('Member')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->editOptionForm(User::getForm())
                        ->createOptionForm(User::getForm())
                        ->required(),
                    TextInput::make('credit_amount')
                        ->label('Amount')
                        ->required()
                        ->numeric()
                        ->hintIcon('heroicon-o-currency-dollar')
                        ->prefix('Kes'),
                ]),
        ];

    }
}
