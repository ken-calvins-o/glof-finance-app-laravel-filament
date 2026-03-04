<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Filament\Resources\PayableResource;
use App\Services\PayableCreationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $payables = app(PayableCreationService::class)->create($data);

        // Filament expects a single record; we return the last created payable (same behavior as before).
        return $payables->last();
    }

}
