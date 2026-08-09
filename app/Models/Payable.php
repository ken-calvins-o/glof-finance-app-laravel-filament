<?php

namespace App\Models;

use App\Support\Money;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\HtmlString;

class Payable extends Model
{
    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
        'from_savings' => 'boolean',
        'is_general' => 'boolean',
    ];

    public function months(): BelongsToMany
    {
        return $this->belongsToMany(Month::class, 'monthly_payable')
            ->using(MonthlyPayable::class);
    }

    public function years(): BelongsToMany
    {
        return $this->belongsToMany(Year::class, 'payable_year')
            ->using(PayableYear::class);
    }

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

    /**
     * Recording a payment out, as three questions instead of one long page.
     *
     * The old form put every field on screen at once and used a radio button
     * labelled "Shared / Custom" to show and hide half of them. Three specific
     * problems came out of that:
     *
     *  - "Shared" and "Custom" name an implementation, not an outcome. The
     *    question is whether everyone pays the same amount.
     *  - The multi-select for choosing who to skip was titled "Exclude/Leave
     *    out members" and sat inside a second box with the identical title,
     *    and it silently capped selections at eight members.
     *  - Nothing ever showed how many members would be charged or what the
     *    payment would cost in total. A shared payment of KES 750 across
     *    thirty-two members is KES 24,000 leaving the group, and the treasurer
     *    committed to it without ever seeing that number.
     *
     * A wizard fixes the ordering (decide the split before being asked for
     * amounts) and buys a review step, which is where that total now lives.
     */
    public static function getSteps(): array
    {
        return [
            Wizard\Step::make('Payment')
                ->label('What and when')
                ->description('The fund and the period')
                ->icon('heroicon-o-rectangle-group')
                ->columns(3)
                ->schema([
                    Select::make('account_id')
                        ->relationship('account', 'name')
                        ->label('Which fund is this paid from?')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required()
                        ->columnSpanFull(),

                    Select::make('month_id')
                        ->label('Month it belongs to')
                        ->options(fn () => Month::orderBy('id')->pluck('name', 'id')->all())
                        ->default(fn () => Month::where('name', now()->format('F'))->value('id'))
                        ->native(false)
                        ->searchable()
                        ->required(),

                    Select::make('year_id')
                        ->label('Year')
                        ->options(fn () => Year::orderByDesc('year')->pluck('year', 'id')->all())
                        ->default(fn () => Year::where('year', now()->year)->value('id'))
                        ->native(false)
                        ->searchable()
                        ->required(),

                    ToggleButtons::make('is_general')
                        ->label('How is the cost split?')
                        ->boolean('Everyone pays the same', 'Different amount each')
                        ->icons([
                            1 => 'heroicon-o-user-group',
                            0 => 'heroicon-o-adjustments-horizontal',
                        ])
                        ->default(true)
                        ->required()
                        ->inline()
                        ->grouped()
                        ->live()
                        ->helperText(fn ($state) => $state
                            ? 'Every member is charged the same amount, apart from anyone you leave out.'
                            : 'You choose the members and set each one’s amount yourself.')
                        ->columnSpanFull(),
                ]),

            Wizard\Step::make('Members')
                ->label('Who pays')
                ->description('Amounts and who is included')
                ->icon('heroicon-o-users')
                ->schema([
                    /* ----- Everyone pays the same ----- */
                    TextInput::make('total_amount')
                        ->label('Amount each member pays')
                        ->helperText('This is charged to every included member individually, not split between them.')
                        ->prefix('KES')
                        ->numeric()
                        ->minValue(1)
                        ->required(fn (Get $get) => (bool) $get('is_general'))
                        ->live(onBlur: true)
                        ->visible(fn (Get $get) => (bool) $get('is_general')),

                    ToggleButtons::make('from_savings')
                        ->label('How is it being paid?')
                        ->boolean('From their savings', 'They pay separately')
                        ->default(false)
                        ->inline()
                        ->grouped()
                        ->helperText('"From their savings" takes the money the members already hold with the group.')
                        ->visible(fn (Get $get) => (bool) $get('is_general')),

                    Select::make('user_id')
                        ->label('Leave anyone out?')
                        ->placeholder('Nobody — charge every member')
                        ->helperText('Members picked here are skipped. Everyone else is charged.')
                        ->options(fn () => User::orderBy('name')->pluck('name', 'id')->all())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->visible(fn (Get $get) => (bool) $get('is_general')),

                    /* ----- Different amount each ----- */
                    Repeater::make('users')
                        ->label('Members and amounts')
                        ->addActionLabel('Add another member')
                        ->defaultItems(1)
                        ->minItems(1)
                        ->live()
                        ->reorderable(false)
                        ->itemLabel(fn (array $state): ?string => $state['user_id']
                            ? trim((User::find($state['user_id'])?->name ?? '')
                                . (is_numeric($state['total_amount'] ?? null) ? ' · ' . Money::kes($state['total_amount']) : ''))
                            : null)
                        ->columns(12)
                        ->schema([
                            Select::make('user_id')
                                ->label('Member')
                                ->options(fn () => User::orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->columnSpan(['default' => 12, 'md' => 5]),

                            TextInput::make('total_amount')
                                ->label('Amount')
                                ->prefix('KES')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->live(onBlur: true)
                                ->columnSpan(['default' => 12, 'md' => 3]),

                            ToggleButtons::make('from_savings')
                                ->label('Paid from')
                                ->boolean('Savings', 'Separately')
                                ->default(false)
                                ->inline()
                                ->grouped()
                                ->columnSpan(['default' => 12, 'md' => 4]),
                        ])
                        ->visible(fn (Get $get) => ! $get('is_general')),
                ]),

            Wizard\Step::make('Review')
                ->label('Review')
                ->description('Check before saving')
                ->icon('heroicon-o-check-circle')
                ->schema([
                    Placeholder::make('summary')
                        ->hiddenLabel()
                        ->content(fn (Get $get) => static::summarise($get)),
                ]),
        ];
    }

    public static function getForm(): array
    {
        return [
            Wizard::make(static::getSteps())->columnSpanFull(),
        ];
    }

    /**
     * How many members a shared payment will actually charge.
     *
     * Mirrors what the creation service does — everyone except the members
     * explicitly left out — so the number on the review step is the number
     * that will be saved, not an approximation of it.
     */
    public static function includedMemberCount(array $excludedIds): int
    {
        return User::query()
            ->when($excludedIds !== [], fn ($query) => $query->whereNotIn('id', $excludedIds))
            ->count();
    }

    /**
     * The review step: who is charged, how much each, and what it comes to.
     */
    protected static function summarise(Get $get): HtmlString
    {
        $fund = $get('account_id') ? Account::find($get('account_id'))?->name : null;
        $month = $get('month_id') ? Month::find($get('month_id'))?->name : null;
        $year = $get('year_id') ? Year::find($get('year_id'))?->year : null;

        if ((bool) $get('is_general')) {
            $excluded = array_filter((array) ($get('user_id') ?? []));
            $each = (float) ($get('total_amount') ?? 0);
            $count = static::includedMemberCount($excluded);
            $total = $each * $count;

            $excludedNames = $excluded
                ? User::whereIn('id', $excluded)->orderBy('name')->pluck('name')->implode(', ')
                : null;

            $rows = [
                'Fund' => $fund ?? '—',
                'Period' => trim(($month ?? '') . ' ' . ($year ?? '')) ?: '—',
                'Members charged' => $count . ($excludedNames ? ' (skipping ' . e($excludedNames) . ')' : ' — everyone'),
                'Each member pays' => Money::kes($each),
            ];
        } else {
            $rows = collect($get('users') ?? [])->filter(fn ($row) => filled($row['user_id'] ?? null));
            $total = (float) $rows->sum(fn ($row) => (float) ($row['total_amount'] ?? 0));

            $rows = [
                'Fund' => $fund ?? '—',
                'Period' => trim(($month ?? '') . ' ' . ($year ?? '')) ?: '—',
                'Members charged' => (string) $rows->count(),
            ];
        }

        $list = collect($rows)
            ->map(fn ($value, $label) => sprintf(
                '<div class="flex justify-between gap-6 py-2 border-b border-gray-100 dark:border-gray-800">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">%s</dt>
                    <dd class="text-sm font-medium text-right text-gray-950 dark:text-white">%s</dd>
                 </div>',
                e($label),
                $value,
            ))
            ->implode('');

        return new HtmlString(sprintf(
            '<dl class="w-full">%s
                <div class="flex justify-between gap-6 pt-3">
                    <dt class="text-sm font-semibold text-gray-950 dark:text-white">Total leaving the group</dt>
                    <dd class="text-lg font-semibold text-danger-600 dark:text-danger-400">%s</dd>
                </div>
             </dl>',
            $list,
            e(Money::kes($total)),
        ));
    }
}
