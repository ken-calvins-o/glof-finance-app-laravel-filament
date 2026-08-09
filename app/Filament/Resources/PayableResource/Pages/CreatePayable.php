<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Filament\Resources\PayableResource;
use App\Models\Payable;
use App\Services\PayableCreationService;
use App\Support\Money;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayable extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = PayableResource::class;

    protected static ?string $title = 'Record money out';

    public function getSubheading(): ?string
    {
        return 'Charge a group expense to members. You will see the total before anything is saved.';
    }

    protected function getSteps(): array
    {
        return Payable::getSteps();
    }

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()->label('Record payment');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Payment recorded';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $payables = app(PayableCreationService::class)->create($data);

        $this->chargedCount = $payables->count();
        $this->chargedTotal = (float) $payables->sum('total_amount');

        // Filament expects a single record; we return the last created payable (same behavior as before).
        return $payables->last();
    }

    protected int $chargedCount = 0;

    protected float $chargedTotal = 0.0;

    /**
     * Report what actually happened rather than "Created". One submission here
     * writes a row per member, so the count and the total are the only way the
     * treasurer can confirm the charge landed the way they intended.
     */
    protected function getCreatedNotification(): ?\Filament\Notifications\Notification
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title('Payment recorded')
            ->body(sprintf(
                '%d %s charged, %s in total.',
                $this->chargedCount,
                $this->chargedCount === 1 ? 'member was' : 'members were',
                Money::kes($this->chargedTotal),
            ));
    }
}
