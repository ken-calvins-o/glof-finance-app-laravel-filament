<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToOwnRecords;
use App\Filament\Navigation;
use App\Filament\Resources\SavingResource\Pages;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Saving;
use App\Support\Money;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

/**
 * The savings ledger.
 *
 * This screen was called "Transaction Logs" and listed every ledger row the
 * system had ever written, for every member, with no grouping and no context —
 * 288 rows of credit / debit / balance / net worth. It is genuinely useful as
 * an audit trail, which is what it is presented as now: something you consult
 * when a figure needs explaining, rather than a place you browse.
 *
 * The question it used to be asked instead — "what does this member have?" —
 * is answered on the member's statement page, where it belongs.
 */
class SavingResource extends Resource
{
    use RestrictsToOwnRecords;

    protected static ?string $model = Saving::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $activeNavigationIcon = 'heroicon-s-queue-list';

    protected static ?string $navigationGroup = Navigation::REPORTS;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Savings ledger';

    protected static ?string $modelLabel = 'ledger entry';

    protected static ?string $pluralModelLabel = 'savings ledger';

    /**
     * A row count told the treasurer nothing they could act on, and it grew by
     * several rows every time anything was recorded.
     */
    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function canCreate(): bool
    {
        // Ledger rows are written by the transactions that cause them. Letting
        // someone type one by hand would put the running balances out of step
        // with the collections and repayments that produced them.
        return false;
    }

    protected static function baseEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function form(Form $form): Form
    {
        return $form->schema(Saving::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->defaultSort('id', 'desc')
            ->filtersTriggerAction(fn ($action) => $action->button()->label('Filter'))
            ->emptyStateIcon('heroicon-o-queue-list')
            ->emptyStateHeading('Nothing in the ledger yet')
            ->emptyStateDescription('Every collection, payment, loan and repayment writes a line here automatically.')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('j M Y, g:ia')
                    ->sortable()
                    ->description(fn (Saving $record) => '#' . $record->id),

                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(28)
                    ->getStateUsing(fn (Saving $record) => $record->user?->avatar_url),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Member')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('credit_amount')
                    ->label('In')
                    ->alignEnd()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => (float) $state > 0 ? Money::kes($state) : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('debit_amount')
                    ->label('Out')
                    ->alignEnd()
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => (float) $state > 0 ? Money::kes($state) : '—')
                    ->sortable(),

                MoneyColumn::make('balance', 'Savings after')
                    ->tooltip('The member’s savings balance immediately after this transaction'),

                MoneyColumn::make('net_worth', 'Net worth after')
                    ->tooltip('Everything the member had put in, less what they had drawn, immediately after this transaction')
                    ->visibleFrom('lg'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Member')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->visible(fn () => (bool) auth()->user()?->isAdmin()),

                Tables\Filters\Filter::make('recorded_between')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))),

                Tables\Filters\TernaryFilter::make('direction')
                    ->label('Direction')
                    ->placeholder('Both')
                    ->trueLabel('Money in only')
                    ->falseLabel('Money out only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('credit_amount', '>', 0),
                        false: fn (Builder $query) => $query->where('debit_amount', '>', 0),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Download as Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename(date('Y-m-d') . ' - Savings ledger')
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
            'index' => Pages\ListSavings::route('/'),
        ];
    }
}
