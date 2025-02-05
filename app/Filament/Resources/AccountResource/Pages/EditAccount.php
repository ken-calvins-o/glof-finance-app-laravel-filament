<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;
use App\Models\Debt;
use App\Models\Saving;
use App\Models\Receivable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
