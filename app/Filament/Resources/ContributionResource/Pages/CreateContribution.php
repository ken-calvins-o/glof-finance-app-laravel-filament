<?php

namespace App\Filament\Resources\ContributionResource\Pages;

use App\Filament\Resources\ContributionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateContribution extends CreateRecord
{
    protected static string $resource = ContributionResource::class;

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('saveContribution')
                ->label('Save Contribution')
                ->requiresConfirmation() // Display confirmation modal
                ->modalDescription('Are you sure you want to save this contribution? This action cannot be undone.')
                ->modalHeading('Confirm Contribution Submission')
                ->color('info')
                ->action(fn () => $this->create()), // Calls the default create method
        ];
    }
}
