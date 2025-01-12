<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registration_fee' => 'decimal:2',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => RoleEnum::class,
        ];
    }

    public function debts():HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function savings():HasMany
    {
        return $this->hasMany(Saving::class);
    }

    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_user')
            ->using(AccountUser::class) // Specify the pivot model
            ->withPivot([
                'amount_due',
            ]);
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public static function getForm(): array
    {
        return [
            Section::make('User details')
                ->icon('heroicon-s-pencil-square')
                ->columns(['md' => 2, 'lg' => 2])
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-user-circle'),
                    TextInput::make('email')
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-envelope')
                        ->rules(function ($get) {
                            return [
                                'required',
                                'string',
                                Rule::unique('users', 'email')->ignore($get('id')), // Ignore uniqueness validation for the current record's ID
                            ];
                        })
                        ->required(),
                    PhoneInput::make('phone'),
                    Select::make('role')
                        ->enum(RoleEnum::class)
                        ->default(RoleEnum::Administrator)
                        ->options(collect(RoleEnum::cases())->mapWithKeys(function ($case) {
                            return [$case->value => ucwords(str_replace(' ', ' ', $case->value))];  // Formats the labels
                        }))
                        ->required(),
                    TextInput::make('registration_fee')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->hintIcon('heroicon-o-currency-dollar')
                        ->prefix('KES'),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn(?string $state): bool => filled($state))
                        ->label('Password')
                        ->required(fn(string $context): bool => $context === 'create')
                        ->visible(fn(string $context): bool => $context === 'create')
                ]),
        ];
    }

}
