<?php

namespace App\Filament\Widgets;

use App\Enums\DebtStatusEnum;
use App\Filament\Actions\RecordRepaymentAction;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Debt;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The dashboard's one piece of work-to-do.
 *
 * A dashboard that only reports totals is a poster. This is the list a
 * treasurer chases each month, and the repayment can be recorded straight from
 * it, so the most common follow-up action is on the home screen rather than
 * four clicks away.
 */
class WhoOwesUs extends BaseWidget
{
    protected static ?string $heading = 'Who owes the group';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Debt::query()
                    ->with(['user', 'account'])
                    ->whereIn('debt_status', DebtStatusEnum::outstandingValues())
                    ->where('outstanding_balance', '>', 0)
                    ->orderByDesc('outstanding_balance')
            )
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10, 25])
            ->emptyStateIcon('heroicon-o-check-badge')
            ->emptyStateHeading('Nobody owes the group anything')
            ->emptyStateDescription('Every loan and every fund balance is settled. Enjoy it while it lasts.')
            ->columns([
                ImageColumn::make('user.avatar')
                    ->label('')
                    ->circular()
                    ->size(32)
                    ->getStateUsing(fn (Debt $record) => $record->user?->avatar_url),

                TextColumn::make('user.name')
                    ->label('Member')
                    ->weight('medium')
                    ->searchable()
                    ->description(fn (Debt $record) => $record->account?->name ?? 'Loan'),

                MoneyColumn::make('outstanding_balance', 'Still owing'),

                TextColumn::make('debt_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->formatStateUsing(fn ($state) => $state->getLabel()),

                TextColumn::make('created_at')
                    ->label('Owing since')
                    ->since()
                    ->tooltip(fn (Debt $record) => $record->created_at?->format('j M Y'))
                    ->toggleable(),
            ])
            ->actions([
                RecordRepaymentAction::make(),
            ]);
    }
}
