<?php

namespace App\Filament\Resources\PayableResource\Pages;

use App\Filament\Resources\PayableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayables extends ListRecords
{
    protected static string $resource = PayableResource::class;

    public function getSubheading(): ?string
    {
        return auth()->user()?->isAdmin()
            ? 'Group expenses charged to members. One row per member per payment.'
            : 'Group expenses that have been charged to you.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Record money out')
                ->icon('heroicon-o-plus'),
        ];
    }
}
