<?php

namespace App\Models;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'interest_amount' => 'decimal:2',
        'date' => 'datetime',
    ];

    public function account():BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getForm()
    {
        return [
            Section::make('Income Details')
                ->icon('heroicon-s-pencil-square')
                ->columns(['md' => 2, 'lg' => 2])
            ->schema([
                TextInput::make('source')
                    ->required()
                    ->maxLength(255),
                TextInput::make('amount')
                    ->label('Amount')
                    ->required()
                    ->numeric()
                    ->minValue('1')
                    ->hintIcon('heroicon-o-currency-dollar')
                    ->prefix('KES'),
                TextInput::make('description')
                    ->maxLength(255)
                    ->default(null),
                DateTimePicker::make('date')
                    ->required(),
            ]),
        ];
    }
}
