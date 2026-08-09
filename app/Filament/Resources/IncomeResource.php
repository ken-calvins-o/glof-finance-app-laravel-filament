<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Navigation;
use App\Filament\Resources\IncomeResource\Pages;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Income;
use App\Support\Money;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

/**
 * What the group earns, as opposed to what members contribute.
 *
 * Joining fees and loan interest both land here automatically. The table used
 * to hide every one of its money columns behind the "toggle columns" menu by
 * default, so the screen opened showing a member's name and nothing else.
 */
class IncomeResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Income::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $activeNavigationIcon = 'heroicon-s-banknotes';

    protected static ?string $navigationGroup = Navigation::MONEY_IN;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Group income';

    protected static ?string $modelLabel = 'income';

    protected static ?string $pluralModelLabel = 'group income';

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'account']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema(Income::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->defaultSort('created_at', 'desc')
            ->filtersTriggerAction(fn ($action) => $action->button()->label('Filter'))
            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateHeading('No income recorded yet')
            ->emptyStateDescription('Joining fees and loan interest are recorded here automatically. You can also add income the group earned in other ways.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Record income'),
            ])
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(32)
                    ->getStateUsing(fn (Income $record) => $record->user?->avatar_url),

                TextColumn::make('user.name')
                    ->label('From')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('origin')
                    ->label('Source')
                    ->badge()
                    ->placeholder('Other')
                    ->color(fn ($state) => match ($state) {
                        'Loan' => 'warning',
                        'Registration Fee' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'Registration Fee' => 'Joining fee',
                        'Loan' => 'Loan interest',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),

                MoneyColumn::withTotal('income_amount', 'Amount')
                    ->formatStateUsing(fn ($state) => (float) $state > 0 ? Money::kes($state) : '—'),

                MoneyColumn::withTotal('interest_amount', 'Interest')
                    ->formatStateUsing(fn ($state) => (float) $state > 0 ? Money::kes($state) : '—'),

                TextColumn::make('account.name')
                    ->label('Fund')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('origin')
                    ->label('Source')
                    ->options([
                        'Registration Fee' => 'Joining fee',
                        'Loan' => 'Loan interest',
                    ]),

                Tables\Filters\SelectFilter::make('account_id')
                    ->relationship('account', 'name')
                    ->label('Fund')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Download as Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename(date('Y-m-d') . ' - Group income')
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
            'index' => Pages\ListIncomes::route('/'),
            'create' => Pages\CreateIncome::route('/create'),
            'edit' => Pages\EditIncome::route('/{record}/edit'),
        ];
    }
}
