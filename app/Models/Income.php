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
            Section::make('Income received')
                ->description('Money the group has earned. Joining fees and loan interest are recorded automatically — use this for anything else.')
                ->icon('heroicon-o-banknotes')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->label('Who did it come from?')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->createOptionForm(User::getForm())
                        ->required(),

                    Select::make('account_id')
                        ->label('Which fund does it belong to?')
                        ->relationship('account', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required(),

                    TextInput::make('income_amount')
                        ->label('Amount')
                        ->prefix('KES')
                        ->numeric()
                        ->minValue(1)
                        ->required(),

                    TextInput::make('origin')
                        ->label('What kind of income is it?')
                        ->placeholder('e.g. Fine, Fundraiser, Bank interest')
                        ->helperText('A short label so this entry makes sense on a report later.')
                        ->maxLength(255),
                ]),
        ];
    }
}
