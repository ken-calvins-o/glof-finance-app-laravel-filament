<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use App\Models\Debt;
use App\Services\DebtRepaymentService;
use App\Support\Money;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class EditDebt extends EditRecord
{
    protected static string $resource = DebtResource::class;

    protected static ?string $title = 'Repayment';

    public function getSubheading(): ?string
    {
        return 'Record what this member has paid back.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Write off')
                ->icon('heroicon-o-trash')
                ->modalHeading('Write off this debt?')
                ->modalDescription('This removes the debt entirely, as though it had never been owed. It does not credit the member with a payment and it does not adjust their savings. Only do this when the group has agreed to forgive the balance.')
                ->modalSubmitActionLabel('Yes, write it off'),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        // The service sends its own, more specific notification.
        return null;
    }

    /**
     * Apply the repayment.
     *
     * All of the balance, loan, fund and savings bookkeeping that used to sit
     * inline here now lives in DebtRepaymentService, so that recording a
     * repayment from the debts table, the dashboard or this page all do exactly
     * the same thing. Behaviour is unchanged; the failure paths just report
     * themselves properly now instead of surfacing a raw exception page.
     */
    protected function handleRecordUpdate(Model $record, array $data): Debt
    {
        /** @var Debt $record */
        try {
            $debt = app(DebtRepaymentService::class)->apply(
                $record,
                (float) ($data['repayment_amount'] ?? 0),
                (bool) ($data['from_savings'] ?? false),
            );
        } catch (RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('Repayment not recorded')
                ->body($e->getMessage())
                ->persistent()
                ->send();

            throw ValidationException::withMessages([
                'data.repayment_amount' => $e->getMessage(),
            ]);
        }

        Notification::make()
            ->success()
            ->title('Repayment recorded')
            ->body((float) $debt->outstanding_balance > 0
                ? sprintf(
                    '%s still owes %s on %s.',
                    $debt->user?->name ?? 'This member',
                    Money::kes($debt->outstanding_balance),
                    $debt->account?->name ?? 'their loan',
                )
                : sprintf('%s has cleared this debt in full.', $debt->user?->name ?? 'This member'))
            ->send();

        return $debt;
    }
}
