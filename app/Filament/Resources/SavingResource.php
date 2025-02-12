<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavingResource\Pages;
use App\Filament\Resources\SavingResource\Widgets\WealthSummaryChartWidget;
use App\Filament\Resources\SavingResource\Widgets\WealthSummaryWidget;
use App\Models\Saving;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Webbingbrasil\FilamentAdvancedFilter\Filters\DateFilter;
use Webbingbrasil\FilamentAdvancedFilter\Filters\NumberFilter;

class SavingResource extends Resource
{
    protected static ?string $model = Saving::class;

    protected static ?string $navigationGroup = 'Assets';

    public static function getPluralLabel(): string
    {
        return 'Statements'; // This changes the plural form displayed in the UI
    }

    public static function getNavigationBadge(): ?string
    {
        return Saving::count();
    }

    protected static ?string $navigationIcon = 'heroicon-o-bookmark-square';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Saving::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->filtersTriggerAction(function ($action) {
                return $action->button()->label('Filter');
            })
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->label('Photo')
                    ->defaultImageUrl(function ($record) {
                        return 'https://ui-avatars.com/api/?background=EA580C&color=fff&name=' . urlencode($record->user->name);
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Member')
                    ->searchable()
                    ->numeric()
                    ->sortable(),
                // Add cumulative amount column
                Tables\Columns\TextColumn::make('credit_amount')
                    ->label('Credit Amount')
                    ->prefix('Kes ')
                    ->formatStateUsing(fn($state) => number_format($state, 2))
                    ->searchable(),
                Tables\Columns\TextColumn::make('debit_amount')
                    ->label('Debit Amount')
                    ->prefix('Kes ')
                    ->formatStateUsing(fn($state) => number_format($state, 2))
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Savings Balance')
                    ->prefix('Kes ')
                    ->formatStateUsing(fn($state) => number_format($state, 2))
                    ->searchable(),

                // Add cumulative net worth column
                Tables\Columns\TextColumn::make('net_worth')
                    ->prefix('Kes ')
                    ->formatStateUsing(fn($state) => number_format($state, 2))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                DateFilter::make('created_at')->debounce(700),
                NumberFilter::make('net_worth')->debounce(700)
            ])
            ->actions([

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exports([
                        ExcelExport::make()
                            ->withFilename(date('Y-m-d') . ' - Statements')
                            ->fromTable()
                            ->askForFilename()
                            ->except('avatar'),
                    ]),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            WealthSummaryWidget::class,
            WealthSummaryChartWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavings::route('/'),
            'create' => Pages\CreateSaving::route('/create'),
//            'edit' => Pages\EditSaving::route('/{record}/edit'),
        ];
    }
}
