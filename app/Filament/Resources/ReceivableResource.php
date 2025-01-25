<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceivableResource\Pages;
use App\Models\Receivable;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Webbingbrasil\FilamentAdvancedFilter\Filters\DateFilter;
use Webbingbrasil\FilamentAdvancedFilter\Filters\NumberFilter;
use Webbingbrasil\FilamentAdvancedFilter\Filters\TextFilter;

class ReceivableResource extends Resource
{
    protected static ?string $model = Receivable::class;

    public static function getNavigationBadge(): ?string
    {
        return Receivable::count();
    }

    public static function getPluralLabel(): string
    {
        return 'Receivables (Credits)'; // This changes the plural form displayed in the UI
    }

    protected static ?string $navigationIcon = 'heroicon-o-arrows-pointing-in';

    protected static ?string $navigationGroup = 'Accounts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Receivable::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->label('Photo')
                    ->defaultImageUrl(function ($record) {
                        return 'https://ui-avatars.com/api/?background=EA580C&color=fff&name=' . urlencode($record->user->name);
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Member Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Account Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount_contributed')
                    ->sortable()
                    ->formatStateUsing(fn($state) => 'KES ' . number_format($state, 2)),
                Tables\Columns\TextColumn::make('total_amount_contributed')
                    ->sortable()
                    ->formatStateUsing(fn($state) => 'KES ' . number_format($state, 2)),
                Tables\Columns\TextColumn::make('months')
                    ->label('Recorded Month')
                    ->getStateUsing(fn ($record) => $record->months->pluck('name')->implode(', ') ?? 'N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('years')
                    ->label('Recorded Year')
                    ->getStateUsing(fn ($record) => $record->years->pluck('year')->implode(', ') ?? 'N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->sortable()
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
                TextFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->debounce(700)
                    ->label('Member Name'),
                DateFilter::make('created_at')->debounce(700),
                NumberFilter::make('net_worth')->debounce(700)
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withFilename(date('Y-m-d') . ' - Statements')
                            ->fromTable()
                            ->askForFilename()
                            ->except('avatar'),
                    ]),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceivables::route('/'),
            'create' => Pages\CreateReceivable::route('/create'),
//            'edit' => Pages\EditReceivable::route('/{record}/edit'),
        ];
    }
}
