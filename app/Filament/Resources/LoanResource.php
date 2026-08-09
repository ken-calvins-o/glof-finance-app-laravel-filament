<?php

namespace App\Filament\Resources;

use App\Enums\DebtStatusEnum;
use App\Filament\Concerns\RestrictsToOwnRecords;
use App\Filament\Navigation;
use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Debt;
use App\Models\Loan;
use App\Support\Money;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoanResource extends Resource
{
    use RestrictsToOwnRecords;

    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $activeNavigationIcon = 'heroicon-s-hand-raised';

    protected static ?string $navigationGroup = Navigation::LENDING;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Loans issued';

    protected static ?string $modelLabel = 'loan';

    /**
     * How much the group currently has lent out — the number that decides
     * whether another loan can be issued this month.
     */
    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->isAdmin()) {
            return null;
        }

        $out = Debt::whereIn('debt_status', DebtStatusEnum::outstandingValues())
            ->whereNull('account_id')
            ->sum('outstanding_balance');

        return $out > 0 ? Money::compact($out) : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Currently out on loan';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'description'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema(Loan::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->defaultSort('created_at', 'desc')
            ->filtersTriggerAction(fn ($action) => $action->button()->label('Filter'))
            ->emptyStateIcon('heroicon-o-hand-raised')
            ->emptyStateHeading('No loans issued yet')
            ->emptyStateDescription('When the group lends money to a member, record it here so the repayment is tracked automatically.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Issue a loan'),
            ])
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(32)
                    ->getStateUsing(fn (Loan $record) => $record->user?->avatar_url),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Member')
                    ->weight('medium')
                    ->description(fn (Loan $record) => $record->description)
                    ->searchable()
                    ->sortable(),

                MoneyColumn::make('amount', 'Borrowed'),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Still owing')
                    ->alignEnd()
                    ->weight('medium')
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success')
                    ->getStateUsing(function (Loan $record) {
                        // The live figure lives on the member's credited-loan
                        // debt; the loan's own balance is the starting point.
                        $debt = $record->user?->debts?->first()
                            ?? Debt::where('user_id', $record->user_id)
                                ->whereNull('account_id')
                                ->orderByDesc('created_at')
                                ->first();

                        return max(0, (float) ($debt->outstanding_balance ?? $record->balance ?? 0));
                    })
                    ->formatStateUsing(fn ($state) => Money::kes($state)),

                Tables\Columns\TextColumn::make('interest')
                    ->label('Rate')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state, Loan $record) => $record->apply_interest
                        ? rtrim(rtrim(number_format((float) $state, 2), '0'), '.') . '% a month'
                        : 'Interest free')
                    ->color(fn (Loan $record) => $record->apply_interest ? null : 'gray')
                    ->toggleable()
                    ->visibleFrom('lg'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date('j M Y')
                    ->sortable()
                    // An overdue date is worth noticing without reading the
                    // status column as well.
                    ->color(fn (Loan $record) => $record->due_date
                        && $record->due_date->isPast()
                        && $record->debt_status !== DebtStatusEnum::Cleared
                            ? 'danger'
                            : null)
                    ->description(fn (Loan $record) => $record->due_date
                        && $record->due_date->isPast()
                        && $record->debt_status !== DebtStatusEnum::Cleared
                            ? 'Overdue'
                            : null),

                Tables\Columns\TextColumn::make('debt_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->icon(fn ($state) => $state->getIcon())
                    ->color(fn ($state) => $state->getColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Issued')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Member')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->visible(fn () => (bool) auth()->user()?->isAdmin()),

                Tables\Filters\SelectFilter::make('debt_status')
                    ->label('Status')
                    ->options(DebtStatusEnum::options())
                    ->multiple(),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue only')
                    ->toggle()
                    ->query(fn (Builder $query) => $query
                        ->whereDate('due_date', '<', today())
                        ->where('debt_status', '!=', DebtStatusEnum::Cleared->value)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Loan $record) => $record->debt_status !== DebtStatusEnum::Cleared
                        && (bool) auth()->user()?->isAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Delete these loans?')
                        ->modalDescription('This removes the loan records only. Any debt already raised against these members stays in place and will still show as owing.'),
                ]),
            ]);
    }

    // Eager-load user's credited-loan debts to avoid N+1 queries when rendering the table
    protected static function baseEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user' => fn ($q) => $q->with(['debts' => function ($q) {
            $q->whereNull('account_id')->orderByDesc('created_at');
        }])]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
