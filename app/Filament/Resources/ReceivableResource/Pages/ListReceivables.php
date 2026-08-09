<?php

namespace App\Filament\Resources\ReceivableResource\Pages;

use App\Filament\Resources\ReceivableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReceivables extends ListRecords
{
    protected static string $resource = ReceivableResource::class;

    public function getSubheading(): ?string
    {
        return auth()->user()?->isAdmin()
            ? 'Every payment the group has received from members.'
            : 'Everything you have paid into the group.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Record money in')
                ->icon('heroicon-o-plus'),
        ];
    }
}
