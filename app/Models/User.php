<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\DebtStatusEnum;
use App\Enums\MemberStatus;
use App\Enums\RoleEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;
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
            'member_status' => MemberStatus::class,
            'role' => RoleEnum::class,
        ];
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function savings(): HasMany
    {
        return $this->hasMany(Saving::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_user')
            ->using(AccountUser::class);
    }

    public function accountCollections(): HasMany
    {
        return $this->hasMany(AccountCollection::class);
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    /* ---------------------------------------------------------------------
     | Role
     |---------------------------------------------------------------------*/

    public function isAdmin(): bool
    {
        return $this->role === RoleEnum::Administrator;
    }

    public function isMember(): bool
    {
        return ! $this->isAdmin();
    }

    public function scopeMembers(Builder $query): Builder
    {
        return $query->where('role', RoleEnum::Member->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('member_status', MemberStatus::Active->value);
    }

    /* ---------------------------------------------------------------------
     | Money at a glance
     |
     | The savings table is an append-only ledger, so "where does this member
     | stand right now" means the newest row. These accessors exist so that no
     | screen has to re-derive that rule.
     |---------------------------------------------------------------------*/

    protected function latestSaving(): ?Saving
    {
        return $this->relationLoaded('savings')
            ? $this->savings->sortByDesc('id')->first()
            : $this->savings()->latest('id')->first();
    }

    public function getSavingsBalanceAttribute(): float
    {
        return (float) ($this->latestSaving()?->balance ?? 0);
    }

    public function getNetWorthAttribute(): float
    {
        return (float) ($this->latestSaving()?->net_worth ?? 0);
    }

    public function getOutstandingDebtAttribute(): float
    {
        return (float) $this->debts()
            ->whereIn('debt_status', DebtStatusEnum::outstandingValues())
            ->sum('outstanding_balance');
    }

    public function getTotalContributedAttribute(): float
    {
        return (float) $this->accountCollections()->sum('amount');
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim((string) $this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: '?';
    }

    /**
     * A consistent avatar for a member, used in every table and header.
     *
     * Drawn locally as an inline SVG rather than fetched from ui-avatars.com,
     * which is what the old tables did. Every row of every table was making a
     * request to a third party, which meant member names leaked off-site, the
     * tables were slow on a weak connection, and the avatars simply vanished
     * offline — exactly the conditions a rural chama meeting runs under.
     *
     * The colour is derived from the name, so a given member always looks the
     * same wherever they appear, which is what makes an avatar scannable.
     */
    public function getAvatarUrlAttribute(): string
    {
        $palette = ['#0F766E', '#1D4ED8', '#7C3AED', '#B45309', '#BE123C', '#0E7490', '#4D7C0F', '#A21CAF'];
        $background = $palette[crc32((string) ($this->name ?? '')) % count($palette)];

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="32" fill="{$background}"/><text x="32" y="33" font-family="Inter, system-ui, sans-serif" font-size="26" font-weight="600" fill="#ffffff" text-anchor="middle" dominant-baseline="central">{$this->initials}</text></svg>
        SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /* ---------------------------------------------------------------------
     | Form
     |---------------------------------------------------------------------*/

    public static function getForm(): array
    {
        return [
            Section::make('Who they are')
                ->description('The basics we need to identify a member and reach them.')
                ->icon('heroicon-o-identification')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Full name')
                        ->placeholder('e.g. Jane Achieng')
                        ->required()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-user-circle')
                        ->columnSpanFull(),
                    TextInput::make('email')
                        ->label('Email address')
                        ->email()
                        ->placeholder('jane@example.com')
                        ->helperText('Only needed if this person should be able to sign in.')
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-envelope')
                        ->rules(function ($get) {
                            return [
                                'string',
                                Rule::unique('users', 'email')->ignore($get('id')),
                            ];
                        }),
                    PhoneInput::make('phone')
                        ->label('Phone number')
                        ->helperText('Used on statements and for M-PESA reconciliation.'),
                ]),

            Section::make('Membership')
                ->description('What this person can do in the app, and where they stand in the group.')
                ->icon('heroicon-o-shield-check')
                ->columns(2)
                ->schema([
                    Select::make('role')
                        ->label('Access level')
                        ->options(RoleEnum::options())
                        ->default(RoleEnum::Member->value)
                        ->native(false)
                        ->required()
                        ->live()
                        ->helperText(fn ($state) => $state
                            ? (RoleEnum::tryFrom($state)?->getDescription() ?? null)
                            : 'Members only ever see their own money.'),
                    Select::make('member_status')
                        ->label('Membership status')
                        ->options(MemberStatus::options())
                        ->default(MemberStatus::Active->value)
                        ->native(false)
                        ->required()
                        ->live()
                        ->helperText(fn ($state) => $state
                            ? (MemberStatus::tryFrom($state)?->getDescription() ?? null)
                            : null),
                    TextInput::make('registration_fee')
                        ->label('Joining fee paid')
                        ->helperText('Recorded as group income and credited to the member on the day they join.')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->prefix('KES')
                        ->columnSpanFull(),
                    TextInput::make('password')
                        ->label('Sign-in password')
                        ->helperText('Leave blank if this member will not sign in.')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->visible(fn (string $context): bool => $context === 'create')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
