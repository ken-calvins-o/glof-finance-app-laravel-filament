<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\LoanResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLoans extends ListRecords
{
    protected static string $resource = LoanResource::class;

    public function getSubheading(): ?string
    {
        return auth()->user()?->isAdmin()
            ? 'Every loan the group has issued, and where each one stands.'
            : 'Loans the group has issued to you.';
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Being repaid')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('debt_status', '!=', DebtStatusEnum::Cleared->value)),

            'overdue' => Tab::make('Overdue')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereDate('due_date', '<', today())
                    ->where('debt_status', '!=', DebtStatusEnum::Cleared->value))
                ->badgeColor('danger')
                ->badge(fn () => LoanResource::getModel()::query()
                    ->whereDate('due_date', '<', today())
                    ->where('debt_status', '!=', DebtStatusEnum::Cleared->value)
                    ->count() ?: null),

            'cleared' => Tab::make('Fully repaid')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('debt_status', DebtStatusEnum::Cleared->value)),

            'all' => Tab::make('All loans'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'active';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Issue a loan')
                ->icon('heroicon-o-plus'),
        ];
    }
}
