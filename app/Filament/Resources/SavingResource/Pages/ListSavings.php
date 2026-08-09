<?php

namespace App\Filament\Resources\SavingResource\Pages;

use App\Filament\Resources\SavingResource;
use Filament\Resources\Pages\ListRecords;

class ListSavings extends ListRecords
{
    protected static string $resource = SavingResource::class;

    public function getSubheading(): ?string
    {
        return auth()->user()?->isAdmin()
            ? 'Every movement of money, in the order it happened. Use this to trace how a balance got to where it is.'
            : 'Every movement on your savings, in the order it happened.';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
