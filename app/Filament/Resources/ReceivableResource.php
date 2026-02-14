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
        return 'Collections (Credits/Receivables)'; // This changes the plural form displayed in the UI
    }

    /**
     * @return string|null
     */
    public static function getNavigationLabel(): string
    {
        return 'Collections'; // This changes the plural form displayed in the UI
    }

    public static function getLabel(): ?string
    {
        return 'collection'; // This changes the plural form displayed in the UI
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
                    ->label('Amount Contributed')
                    ->sortable()
                    ->formatStateUsing(fn($state) => 'Kes ' . number_format($state, 2)),
                Tables\Columns\TextColumn::make('months')
                    ->label('Allocated Month')
                    ->getStateUsing(fn ($record) => $record->months->pluck('name')->implode(', ') ?? 'N/A')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('years')
                    ->label('Allocated Year')
                    ->getStateUsing(fn ($record) => $record->years->pluck('year')->implode(', ') ?? 'N/A')
                    ->toggleable(isToggledHiddenByDefault: true)
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
                Tables\Actions\Action::make('safeDelete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(function (Receivable $record, array $data): void {
                        // Use the domain service to safely delete
                        (new \App\Services\ReceivableService())->safeDelete($record, auth()->id());
                    }),
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
