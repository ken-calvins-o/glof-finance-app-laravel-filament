<?php

namespace App\Models;

use App\Enums\FrequencyTypeEnum;
use App\Enums\MemberStatus;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;

class Account extends Model
{
    use HasFactory;

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'account_user')
            ->using(AccountUser::class);
    }

    public function years(): BelongsToMany
    {
        return $this->belongsToMany(Year::class, 'account_year')
            ->using(AccountYear::class);
    }

    public static function getForm(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->label('Name of account')
                ->maxLength(255),
        ];
    }
}
