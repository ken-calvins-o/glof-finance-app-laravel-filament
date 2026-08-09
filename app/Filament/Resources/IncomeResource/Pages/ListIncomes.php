<?php

namespace App\Filament\Resources\IncomeResource\Pages;

use App\Filament\Resources\IncomeResource;
use App\Support\Money;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIncomes extends ListRecords
{
    protected static string $resource = IncomeResource::class;

    public function getSubheading(): ?string
    {
        $total = IncomeResource::getModel()::query()->sum('income_amount')
            + IncomeResource::getModel()::query()->sum('interest_amount');

        return $total > 0
            ? 'The group has earned ' . Money::kes($total) . ' since it started.'
            : 'What the group has earned, as opposed to what members have contributed.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Record income'),
        ];
    }
}
