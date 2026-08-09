<?php

namespace App\Filament\Actions;

use App\Models\Debt;
use App\Services\DebtRepaymentService;
use App\Support\Money;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;
use RuntimeException;

/**
 * "Record a repayment" as a one-field modal, available wherever a debt is
 * shown.
 *
 * Recording a repayment used to mean opening a full edit page whose first two
 * fields were disabled selects for the member and the fund, and whose
 * "Outstanding balance" field was also disabled — three inputs the treasurer
 * could not touch, in front of the one they came to fill in. Worse, entering
 * too large an amount only raised a warning notification; the form still
 * submitted and failed later with a raw exception.
 *
 * Here the amount is validated against the balance as a rule, the effect is
 * spelled out before submitting, and the whole thing is two clicks from any
 * list.
 */
class RecordRepaymentAction
{
    public static function make(string $name = 'recordRepayment'): Action
    {
        return Action::make($name)
            ->label('Record repayment')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->modalHeading(fn (Debt $record) => 'Repayment from ' . ($record->user?->name ?? 'member'))
            ->modalDescription(fn (Debt $record) => new HtmlString(sprintf(
                'Owed on <strong>%s</strong>: <strong>%s</strong>',
                e($record->account?->name ?? 'Loan'),
                e(Money::kes($record->outstanding_balance)),
            )))
            ->modalSubmitActionLabel('Record repayment')
            ->modalWidth('lg')
            ->visible(fn (Debt $record) => (float) $record->outstanding_balance > 0
                && (bool) auth()->user()?->isAdmin())
            ->form([
                TextInput::make('repayment_amount')
                    ->label('Amount repaid')
                    ->prefix('KES')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    // The validation the old form only warned about.
                    ->maxValue(fn (Debt $record) => (float) $record->outstanding_balance)
                    ->validationMessages([
                        'max' => 'That is more than this member owes.',
                    ])
                    ->live(onBlur: true)
                    ->hint(fn (Debt $record) => 'Owing: ' . Money::kes($record->outstanding_balance))
                    ->helperText('Enter the amount actually received. Part payments are fine.'),

                ToggleButtons::make('from_savings')
                    ->label('Where is this money coming from?')
                    ->boolean('Their savings', 'A fresh payment')
                    ->default(false)
                    ->inline()
                    ->grouped()
                    ->live()
                    ->helperText('Choose "their savings" only when the group is moving money the member already holds with us.'),

                Placeholder::make('effect')
                    ->label('What this will do')
                    ->content(fn (Debt $record, $get) => app(DebtRepaymentService::class)->describe(
                        $record,
                        is_numeric($get('repayment_amount')) ? (float) $get('repayment_amount') : null,
                        (bool) $get('from_savings'),
                    ) ?? 'Enter an amount to see the effect.'),
            ])
            ->action(function (Debt $record, array $data): void {
                try {
                    $debt = app(DebtRepaymentService::class)->apply(
                        $record,
                        (float) $data['repayment_amount'],
                        (bool) ($data['from_savings'] ?? false),
                    );
                } catch (RuntimeException $e) {
                    Notification::make()
                        ->danger()
                        ->title('Repayment not recorded')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Repayment recorded')
                    ->body((float) $debt->outstanding_balance > 0
                        ? sprintf(
                            '%s still owes %s.',
                            $debt->user?->name ?? 'This member',
                            Money::kes($debt->outstanding_balance),
                        )
                        : sprintf('%s has cleared this debt in full.', $debt->user?->name ?? 'This member'))
                    ->send();
            });
    }
}
