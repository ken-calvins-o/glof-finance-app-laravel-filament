<?php

namespace App\Models;

use App\Enums\DebtStatusEnum;
use App\Services\DebtRepaymentService;
use App\Support\Money;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;

class Debt extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'account_id' => 'integer',
        'outstanding_balance' => 'decimal:2',
        'repayment_amount' => 'decimal:2',
        'from_savings' => 'boolean',
        'debt_status' => DebtStatusEnum::class,
        'last_interest_applied_on' => 'date',
    ];

    // Define relationships
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Bulk update for debts
    public static function bulkUpdateDebtStatus($debtIds)
    {
        $debts = self::whereIn('id', $debtIds)->get();

        foreach ($debts as $debt) {
            $debt->update(['debt_status' => $debt->outstanding_balance <= 0 ? DebtStatusEnum::Cleared : DebtStatusEnum::Pending]);
        }
    }

    /**
     * Recording a repayment on the debt's own page.
     *
     * The old form opened with three disabled inputs — member, fund and
     * outstanding balance — before reaching the single field the treasurer came
     * to fill in. Read-only facts are not form fields, so they are now shown as
     * text, and the page is down to the two questions that actually have
     * answers: how much, and where from.
     *
     * The amount is validated against the balance. Previously typing too much
     * only raised a warning notification and let you submit anyway, at which
     * point the save failed with an unhandled exception.
     */
    public static function getForm(): array
    {
        return [
            Section::make('The debt')
                ->icon('heroicon-o-scale')
                ->columns(3)
                ->schema([
                    Placeholder::make('member')
                        ->label('Member')
                        ->content(fn (?Debt $record) => $record?->user?->name ?? '—'),

                    Placeholder::make('owed_on')
                        ->label('Owed on')
                        ->content(fn (?Debt $record) => $record?->account?->name ?? 'Loan'),

                    Placeholder::make('balance')
                        ->label('Outstanding balance')
                        ->content(fn (?Debt $record) => new HtmlString(
                            '<span class="text-lg font-semibold text-danger-600">'
                            . e(Money::kes($record?->outstanding_balance ?? 0))
                            . '</span>'
                        )),
                ]),

            Section::make('Record a repayment')
                ->description('Enter what the member has actually paid. Part payments are fine.')
                ->icon('heroicon-o-banknotes')
                ->columns(2)
                ->schema([
                    TextInput::make('repayment_amount')
                        ->label('Amount repaid')
                        ->prefix('KES')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        // Real validation, rather than a notification that did
                        // not stop the form from submitting.
                        ->maxValue(fn (?Debt $record) => (float) ($record?->outstanding_balance ?? 0))
                        ->validationMessages([
                            'max' => 'That is more than this member owes.',
                        ])
                        ->live(onBlur: true),

                    ToggleButtons::make('from_savings')
                        ->label('Where is the money coming from?')
                        ->boolean('Their savings', 'A fresh payment')
                        ->default(false)
                        ->inline()
                        ->grouped()
                        ->live(),

                    Placeholder::make('effect')
                        ->label('What this will do')
                        ->content(fn (?Debt $record, Get $get) => $record
                            ? (app(DebtRepaymentService::class)->describe(
                                $record,
                                is_numeric($get('repayment_amount')) ? (float) $get('repayment_amount') : null,
                                (bool) $get('from_savings'),
                            ) ?? 'Enter an amount to see the effect.')
                            : null)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
