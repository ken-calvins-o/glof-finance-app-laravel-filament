<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictsToOwnRecords;
use App\Filament\Navigation;
use App\Filament\Resources\PayableResource\Pages;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Payable;
use App\Support\Money;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class PayableResource extends Resource
{
    use RestrictsToOwnRecords;

    protected static ?string $model = Payable::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-up-tray';

    protected static ?string $navigationGroup = Navigation::MONEY_OUT;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'payment';

    protected static ?string $pluralModelLabel = 'payments';

    protected static ?string $navigationLabel = 'Payments';

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->isAdmin()) {
            return null;
        }

        $total = static::getModel()::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total_amount');

        return $total > 0 ? Money::compact($total) : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Paid out so far in ' . now()->format('F');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'account.name'];
    }

    protected static function baseEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'account', 'months', 'years']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema(Payable::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->defaultSort('created_at', 'desc')
            ->filtersTriggerAction(fn ($action) => $action->button()->label('Filter'))
            ->emptyStateIcon('heroicon-o-arrow-up-tray')
            ->emptyStateHeading('No payments recorded yet')
            ->emptyStateDescription('Group expenses charged to members — insurance, bereavement contributions, administration — are recorded here.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Record money out'),
            ])
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(32)
                    ->getStateUsing(fn (Payable $record) => $record->user?->avatar_url),

                TextColumn::make('user.name')
                    ->label('Member charged')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('account.name')
                    ->label('Fund')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                MoneyColumn::withTotal('total_amount', 'Amount'),

                Tables\Columns\IconColumn::make('from_savings')
                    ->label('From savings')
                    ->boolean()
                    ->trueIcon('heroicon-o-wallet')
                    ->falseIcon('heroicon-o-minus-small')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn (Payable $record) => $record->from_savings
                        ? 'Taken from the savings this member holds with the group'
                        : 'Paid separately by the member')
                    ->alignCenter()
                    ->visibleFrom('md'),

                TextColumn::make('is_general')
                    ->label('Split')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Same for all' : 'Individual')
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->toggleable()
                    ->visibleFrom('lg'),

                TextColumn::make('period')
                    ->label('For')
                    ->getStateUsing(fn (Payable $record) => trim(
                        $record->months->pluck('name')->implode(', ') . ' ' . $record->years->pluck('year')->implode(', ')
                    ) ?: '—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime('j M Y, g:ia')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_id')
                    ->relationship('account', 'name')
                    ->label('Fund')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Member')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->visible(fn () => (bool) auth()->user()?->isAdmin()),

                Tables\Filters\TernaryFilter::make('from_savings')
                    ->label('Paid from savings')
                    ->placeholder('Either way')
                    ->trueLabel('From savings only')
                    ->falseLabel('Paid separately only'),
            ])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Download as Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename(date('Y-m-d') . ' - Payments')
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
            'index' => Pages\ListPayables::route('/'),
            'create' => Pages\CreatePayable::route('/create'),
        ];
    }
}
