<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayableResource\Pages;
use App\Filament\Resources\PayableResource\RelationManagers;
use App\Models\Payable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayableResource extends Resource
{
    protected static ?string $model = Payable::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-pointing-out';

    protected static ?string $navigationGroup = 'Accounts';

    public static function getPluralLabel(): string
    {
        return 'Payables (Debits)'; // This changes the plural form displayed in the UI
    }

    public static function getNavigationBadge(): ?string
    {
        return Payable::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema(Payable::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account.name')
                    ->label('Account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListPayables::route('/'),
            'create' => Pages\CreatePayable::route('/create'),
            'edit' => Pages\EditPayable::route('/{record}/edit'),
        ];
    }
}
