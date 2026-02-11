<?php

namespace App\Models;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'source',
        'origin',
        'income_amount',
        'interest_amount',
        'description',
    ];

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
                Select::make('user_id')
                    ->label('Member')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->editOptionForm(User::getForm())
                    ->createOptionForm(User::getForm())
                    ->required(),
                Select::make('account_id')
                    ->label('Account')
                    ->relationship('account', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('income_amount')
                    ->label('Income Amount')
                    ->required()
                    ->numeric()
                    ->minValue('1')
                    ->hintIcon('heroicon-o-currency-dollar')
                    ->prefix('Kes'),
            ])->columns(3),
        ];
    }
}
