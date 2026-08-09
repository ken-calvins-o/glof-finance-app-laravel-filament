<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToOwnRecords;
use App\Filament\Navigation;
use App\Filament\Resources\ReceivableResource\Pages;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Enums\PaymentMode;
use App\Models\Receivable;
use App\Services\ReceivableService;
use App\Support\Money;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ReceivableResource extends Resource
{
    use RestrictsToOwnRecords;

    protected static ?string $model = Receivable::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-down-tray';

    protected static ?string $navigationGroup = Navigation::MONEY_IN;

    protected static ?int $navigationSort = 1;

    /*
    | The old labels shipped the internal word and its translation together —
    | "Collections (Credits/Receivables)" — which asks the reader to hold three
    | synonyms in their head. One name, the one members use, is enough.
    */
    protected static ?string $modelLabel = 'collection';

    protected static ?string $pluralModelLabel = 'collections';

    protected static ?string $navigationLabel = 'Collections';

    protected static ?string $recordTitleAttribute = 'amount_contributed';

    /**
     * A count of every row ever recorded is not information — it grows
     * forever and never asks you to do anything. The badge now reports the
     * only number worth interrupting someone for: what came in this month.
     */
    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->isAdmin()) {
            return null;
        }

        $total = static::getModel()::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount_contributed');

        return $total > 0 ? Money::compact($total) : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Collected so far in ' . now()->format('F');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'account.name'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return ($record->user?->name ?? 'Member') . ' — ' . Money::kes($record->amount_contributed);
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Fund' => $record->account?->name ?? '—',
            'Received' => $record->created_at?->format('j M Y') ?? '—',
        ];
    }

    protected static function baseEloquentQuery(): Builder
    {
        // The tables show a member and a fund on every row; loading them up
        // front avoids a query per row on a 250-row ledger.
        return parent::getEloquentQuery()->with(['user', 'account', 'months', 'years']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema(Receivable::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->defaultSort('created_at', 'desc')
            ->filtersTriggerAction(fn ($action) => $action->button()->label('Filter'))
            ->emptyStateIcon('heroicon-o-arrow-down-tray')
            ->emptyStateHeading('No collections recorded yet')
            ->emptyStateDescription('When the group receives money from members, record it here so it lands on their statements.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Record money in'),
            ])
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(32)
                    ->getStateUsing(fn (Receivable $record) => $record->user?->avatar_url),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Member')
                    ->weight('medium')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Fund')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable(),

                MoneyColumn::withTotal('amount_contributed', 'Amount')
                    // A negative row is arrears, not a payment. Saying so beats
                    // leaving the reader to interpret a minus sign. A repayment
                    // is money in like any other, but worth naming so the
                    // treasurer can see why it appeared without them entering it.
                    ->description(fn (Receivable $record) => match (true) {
                        (float) $record->amount_contributed < 0 => 'Recorded as arrears',
                        $record->isDebtRepayment() => 'Settling a debt',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Paid by')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PaymentMode::tryFrom((string) $state)?->getLabel() ?? $state)
                    ->color(fn ($state) => PaymentMode::tryFrom((string) $state)?->getColor() ?? 'gray')
                    ->icon(fn ($state) => PaymentMode::tryFrom((string) $state)?->getIcon())
                    ->sortable()
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('period')
                    ->label('For')
                    ->getStateUsing(fn (Receivable $record) => trim(
                        $record->months->pluck('name')->implode(', ') . ' ' . $record->years->pluck('year')->implode(', ')
                    ) ?: '—')
                    ->toggleable()
                    ->visibleFrom('lg'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime('j M Y, g:ia')
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

                Tables\Filters\SelectFilter::make('account_id')
                    ->relationship('account', 'name')
                    ->label('Fund')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Paid by')
                    ->options(fn () => collect(PaymentMode::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
                        ->all())
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('source')
                    ->label('Kind of entry')
                    ->placeholder('All collections')
                    ->trueLabel('Debt repayments only')
                    ->falseLabel('Ordinary contributions only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('source', Receivable::SOURCE_DEBT_REPAYMENT),
                        false: fn (Builder $query) => $query->whereNull('source'),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\Filter::make('recorded_between')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Recorded from'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Recorded until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'From ' . \Illuminate\Support\Carbon::parse($data['from'])->format('j M Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'Until ' . \Illuminate\Support\Carbon::parse($data['until'])->format('j M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                /*
                | Deleting a collection is not a row disappearing — it reverses
                | the member's savings, their fund total and any debt the entry
                | settled. The old modal said only "Are you sure?", so the one
                | screen where the consequence needed spelling out was the one
                | screen that stayed silent about it.
                */
                Tables\Actions\Action::make('safeDelete')
                    ->label('Reverse')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalHeading('Reverse this collection?')
                    ->modalDescription(fn (Receivable $record) => sprintf(
                        'This undoes %s recorded for %s on %s. Their savings, their total for this fund, and any debt this entry settled will all be put back the way they were. The entry is kept in the records, not erased.',
                        Money::kes($record->amount_contributed),
                        $record->user?->name ?? 'this member',
                        $record->account?->name ?? 'this fund',
                    ))
                    ->modalSubmitActionLabel('Yes, reverse it')
                    /*
                    | Repayments are not reversible from here. This row is a
                    | record of a debt settlement that DebtRepaymentService
                    | already applied to the debt, the fund and the savings
                    | ledger; reversing it would undo the collection without
                    | restoring the debt, leaving the two out of step. Undo a
                    | repayment by correcting the debt itself.
                    */
                    ->visible(fn (Receivable $record) => (bool) auth()->user()?->isAdmin()
                        && ! $record->isDebtRepayment())
                    ->action(function (Receivable $record): void {
                        try {
                            (new ReceivableService())->safeDelete($record, auth()->id());
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Could not reverse this collection')
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Collection reversed')
                            ->body('Savings, fund totals and debts have been put back.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Download as Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename(date('Y-m-d') . ' - Collections')
                                ->fromTable()
                                ->askForFilename()
                                ->except('avatar'),
                        ]),
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
            'index' => Pages\ListReceivables::route('/'),
            'create' => Pages\CreateReceivable::route('/create'),
        ];
    }
}
