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
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($saving) {
            // Retrieve the user's last saving record
            $lastSaving = self::where('user_id', $saving->user_id)->latest()->first();

            // Set initial values if no previous record exists
            $previousBalance = $lastSaving ? $lastSaving->balance : 0;
            $previousNetWorth = $lastSaving ? $lastSaving->net_worth : 0;

            // Update the balance and net worth
            $saving->balance = $previousBalance + $saving->credit_amount;
            $saving->net_worth = $previousNetWorth + $saving->credit_amount;
            $saving->debit_amount = 0; // Ensuring debit amount remains 0
        });
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
