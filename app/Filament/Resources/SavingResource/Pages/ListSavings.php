<?php

namespace App\Filament\Resources\SavingResource\Pages;

use App\Filament\Resources\SavingResource;
use App\Filament\Resources\SavingResource\Widgets\WealthSummaryWidget;
use App\Filament\Resources\SavingResource\Widgets\WealthSummaryChartWidget;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListSavings extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = SavingResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            WealthSummaryWidget::class,
            WealthSummaryChartWidget::class,
        ];
    }

    protected function getActions(): array
    {
        return [
            $this->makeCreateAction()
                ->label('Add a Saving'), // Custom label for the button
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
