<?php

namespace App\Models;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A fund the group collects into — Bereavement, Insurance, Administration, and
 * so on. Called an "Account" in the schema; shown to users as a "Fund",
 * because "account" already means three other things to a SACCO member (their
 * savings account, a bank account, their login).
 */
class Account extends Model
{
    use HasFactory;

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    /**
     * The running total each member has put into this fund.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(AccountCollection::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'account_user')
            ->using(AccountUser::class);
    }

    public function years(): BelongsToMany
    {
        return $this->belongsToMany(Year::class, 'account_year')
            ->using(AccountYear::class);
    }

    public function getTotalCollectedAttribute(): float
    {
        return (float) $this->collections()->sum('amount');
    }

    public static function getForm(): array
    {
        return [
            Section::make('Fund details')
                ->description('Funds are the pots money is collected into. Members contribute to a fund, and group expenses are paid out of one.')
                ->icon('heroicon-o-rectangle-group')
                ->schema([
                    TextInput::make('name')
                        ->label('Fund name')
                        ->placeholder('e.g. Bereavement')
                        ->helperText('Use a name members will recognise on their statement.')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                ]),
        ];
    }
}
