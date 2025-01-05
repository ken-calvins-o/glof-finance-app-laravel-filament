<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?string $navigationGroup = 'Accounts';

    public static function getNavigationBadge(): ?string
    {
        return Account::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Account::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->filtersTriggerAction(function ($action) {
                return $action->button()->label('Filter');
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('is_general')
                    ->label('Account Type')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return $state ? 'General' : 'Custom';
                    })
                    ->color(fn($state) => $state ? Color::Amber : Color::Green) // primary for General, success for Custom
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('membership_count')
                    ->label('Membership Count')
                    ->badge()
                    ->getStateUsing(fn(Account $record) => $record->users()->count()),
                Tables\Columns\TextColumn::make('budget')
                    ->prefix('KES ')
                    ->formatStateUsing(fn($state) => number_format($state, 2))
                    ->color(fn($record) => !$record->is_general ? Color::Green : null) // Set badge color to green for custom accounts
                    ->getStateUsing(function (Account $record) {
                        return $record->users()->sum('account_user.amount_due');
                    }),
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
