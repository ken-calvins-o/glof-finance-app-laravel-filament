<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\DebtResource;
use App\Support\Money;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDebts extends ListRecords
{
    protected static string $resource = DebtResource::class;

    public function getSubheading(): ?string
    {
        if (! auth()->user()?->isAdmin()) {
            return 'What you still owe the group.';
        }

        $total = DebtResource::getModel()::query()
            ->whereIn('debt_status', DebtStatusEnum::outstandingValues())
            ->sum('outstanding_balance');

        return $total > 0
            ? Money::kes($total) . ' is currently owed to the group.'
            : 'Nothing is currently owed to the group.';
    }

    /**
     * Tabs instead of a filter dropdown: "who still owes me" and "what has been
     * settled" are the two views of this screen, and a tab makes the current
     * one visible rather than hiding it behind a filter panel.
     */
    public function getTabs(): array
    {
        return [
            'owing' => Tab::make('Still owing')
                ->icon('heroicon-o-exclamation-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('outstanding_balance', '>', 0))
                ->badge(fn () => DebtResource::getModel()::where('outstanding_balance', '>', 0)->count()),

            'loans' => Tab::make('From loans')
                ->icon('heroicon-o-hand-raised')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNull('account_id')
                    ->where('outstanding_balance', '>', 0)),

            'arrears' => Tab::make('Fund arrears')
                ->icon('heroicon-o-rectangle-group')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNotNull('account_id')
                    ->where('outstanding_balance', '>', 0)),

            'cleared' => Tab::make('Settled')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('outstanding_balance', '<=', 0)),

            'all' => Tab::make('Everything'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'owing';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
