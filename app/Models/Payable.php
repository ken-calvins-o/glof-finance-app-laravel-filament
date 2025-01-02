<?php

namespace App\Models;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payable extends Model
{
    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accountUser()
    {
        return $this->belongsTo(AccountUser::class, 'user_id', 'user_id')
            ->where('account_id', $this->account_id);
    }

    public static function getAccountSums($accountId)
    {
        $data = AccountUser::where('account_id', $accountId)
            ->selectRaw('SUM(amount_contributed) as total_contributed, SUM(amount_due) as total_due')
            ->first();

        return [
            'total_due' => $data->total_due ?? 0,
            'total_contributed' => $data->total_contributed ?? 0,
        ];
    }

    public static function getForm()
    {
        return [
            Section::make('Payable Details')
                ->schema([
                    Select::make('account_id')
                        ->relationship('account', 'name')
                        ->label('Account Name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($set, $state) {
                            $sums = Payable::getAccountSums($state);
                            $set('total_amount', $sums['total_due']);
                            $set('total_raised', $sums['total_contributed']);
                        }),
                    TextInput::make('total_amount')
                        ->label('Expected Budget')
                        ->prefix('KES')
                        ->readOnly(),
                    TextInput::make('total_raised')
                        ->label('Total Raised')
                        ->prefix('KES')
                        ->disabled()
                        ->readOnly()
                ])
                ->columns(3)
                ->columnSpanFull(),
        ];
    }
}
