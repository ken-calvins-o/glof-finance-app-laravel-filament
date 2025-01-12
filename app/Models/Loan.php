<?php

namespace App\Models;

use App\Enums\DebtStatusEnum;
use App\Enums\PaymentStatusEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'interest' => 'string',
        'due_date' => 'datetime',
        'debt_status' => DebtStatusEnum::class,
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getForm()
    {
        return [
            Section::make('Loan Application Details')
                ->icon('heroicon-s-pencil-square')
                ->columns(['md' => 2, 'lg' => 2])
                ->schema([
                    Select::make('user_id')
                        ->label('Member')
                        ->relationship('user', 'name')
                        ->hintIcon('heroicon-o-user')
                        ->searchable()
                        ->preload()
                        ->editOptionForm(User::getForm())
                        ->createOptionForm(User::getForm())
                        ->required(),
                    TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->label('Amount Requested')
                        ->hintIcon('heroicon-o-currency-dollar')
                        ->prefix('KES')
                        ->maxLength(255)
                        ->reactive()  // Make the input reactive
                        ->afterStateUpdated(fn(callable $set, $state) => $set('balance', $state * 1.01)),  // Set balance as amount + 1% interest

                    TextInput::make('balance')
                        ->label('Balance (Amount + 1% Interest)')
                        ->hintIcon('heroicon-o-currency-dollar')
                        ->prefix('KES')
                        ->maxLength(255)
                        ->readonly()  // Makes it read-only but included in form submission
                        ->reactive(),
                    TextInput::make('interest')
                        ->label('Percentage per month')
                        ->suffix('%')
                        ->default(1)
                        ->hint('Default: 1%')
                        ->required()
                        ->readOnly()
                        ->numeric()  // Restrict input to numeric values only
                        ->minValue(0)  // Prevent negative values, allow only 0 and positive numbers
                        ->maxLength(255),
                    DateTimePicker::make('due_date')
                        ->required(),
                    Select::make('debt_status')
                        ->enum(DebtStatusEnum::class)
                        ->default(DebtStatusEnum::Pending)
                        ->options(DebtStatusEnum::class),
                    Textarea::make('description')
                        ->hint('e.g. To purchase a land')
                        ->columnSpanFull()
                        ->default(null),
                ]),
        ];
    }
}
