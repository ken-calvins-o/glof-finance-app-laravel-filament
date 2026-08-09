<?php

namespace App\Filament\Resources;

use App\Enums\DebtStatusEnum;
use App\Filament\Actions\RecordRepaymentAction;
use App\Filament\Concerns\RestrictsToOwnRecords;
use App\Filament\Navigation;
use App\Filament\Resources\DebtResource\Pages;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Debt;
use App\Services\DebtInterestService;
use App\Support\Money;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class DebtResource extends Resource
{
    use RestrictsToOwnRecords;

    protected static ?string $model = Debt::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $activeNavigationIcon = 'heroicon-s-scale';

    protected static ?string $navigationGroup = Navigation::LENDING;

    protected static ?int $navigationSort = 2;

    /*
    | "Debts" names the record; "Money owed" names what the treasurer is looking
    | at when they open it.
    */
    protected static ?string $navigationLabel = 'Money owed';

    protected static ?string $modelLabel = 'debt';

    protected static ?string $pluralModelLabel = 'money owed';

    /**
     * The count of members currently behind — an actionable number, unlike the
     * total row count the badge used to show.
     */
    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->isAdmin()) {
            return null;
        }

        $count = static::getModel()::query()
            ->whereIn('debt_status', DebtStatusEnum::outstandingValues())
            ->where('outstanding_balance', '>', 0)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Debts still to be repaid';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'account.name'];
    }

    protected static function baseEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'account']);
    }

    /**
     * Debts are never created by hand — they appear when a loan is issued or
     * arrears are recorded. Offering a "New debt" button would only produce
     * records with nothing behind them.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema(Debt::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->defaultSort('outstanding_balance', 'desc')
            ->filtersTriggerAction(fn ($action) => $action->button()->label('Filter'))
            ->emptyStateIcon('heroicon-o-check-badge')
            ->emptyStateHeading('Nothing is owed')
            ->emptyStateDescription('Debts appear here automatically when a loan is issued or when arrears are recorded against a member.')
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(32)
                    ->getStateUsing(fn (Debt $record) => $record->user?->avatar_url),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Member')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Owed on')
                    ->badge()
                    ->color('gray')
                    // A debt with no fund is a loan. Saying "Loan" beats the
                    // internal phrase "Credited Loan", which meant nothing to
                    // anyone who had not read the schema.
                    ->getStateUsing(fn (Debt $record) => $record->account?->name ?? 'Loan')
                    ->searchable()
                    ->sortable(),

                MoneyColumn::withTotal('outstanding_balance', 'Still owing'),

                Tables\Columns\TextColumn::make('debt_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->icon(fn ($state) => $state->getIcon())
                    ->color(fn ($state) => $state->getColor())
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_interest_applied_on')
                    ->label('Interest last added')
                    ->date('j M Y')
                    ->placeholder('Never')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Owing since')
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

                // "Still owing" and "settled" live in the page's tabs, so they
                // are deliberately not repeated here as filters.
                Tables\Filters\SelectFilter::make('debt_status')
                    ->label('Status')
                    ->multiple()
                    ->options(DebtStatusEnum::options()),
            ])
            ->actions([
                // The one-field modal, so a repayment no longer requires
                // opening a full edit page.
                RecordRepaymentAction::make(),

                Tables\Actions\EditAction::make()
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn (Debt $record) => $record->debt_status !== DebtStatusEnum::Cleared
                        && (bool) auth()->user()?->isAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('apply_monthly_interest')
                        ->label('Add this month’s interest')
                        ->icon('heroicon-o-percent-badge')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalIcon('heroicon-o-percent-badge')
                        ->modalHeading('Add monthly interest to the selected debts?')
                        ->modalDescription('Adds 1% to every selected debt that still has a balance, and records it as group income. Debts already cleared are skipped. This normally runs by itself on the 1st of each month — use this only to catch up.')
                        ->modalSubmitActionLabel('Add interest')
                        ->action(function ($records) {
                            $stats = app(DebtInterestService::class)
                                ->applyMonthlyInterestToDebtIds($records->pluck('id'));

                            Notification::make()
                                ->title('Monthly interest added')
                                ->body(sprintf(
                                    '%d %s updated, %s of interest charged.%s',
                                    $stats['processed'],
                                    $stats['processed'] === 1 ? 'debt' : 'debts',
                                    Money::kes($stats['total_interest']),
                                    $stats['errors'] > 0 ? sprintf(' %d could not be processed.', $stats['errors']) : '',
                                ))
                                ->color($stats['errors'] > 0 ? 'warning' : 'success')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => (bool) auth()->user()?->isAdmin()),

                    ExportBulkAction::make()->label('Download as Excel'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDebts::route('/'),
            'edit' => Pages\EditDebt::route('/{record}/edit'),
        ];
    }
}
