<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Navigation;
use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use App\Support\Money;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Funds — the pots money is collected into and paid out of.
 *
 * The model is called Account, but "account" already means a member's savings
 * account, a bank account and a login in this app, so the screen says "fund".
 * It also sits under Setup now: a fund is created once and then left alone,
 * which is not the same kind of thing as a daily collection screen and does not
 * belong beside one in the sidebar.
 */
class AccountResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $activeNavigationIcon = 'heroicon-s-rectangle-group';

    protected static ?string $navigationGroup = Navigation::SETUP;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'fund';

    protected static ?string $pluralModelLabel = 'funds';

    protected static ?string $navigationLabel = 'Funds';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema(Account::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->emptyStateIcon('heroicon-o-rectangle-group')
            ->emptyStateHeading('No funds set up yet')
            ->emptyStateDescription('Create a fund for each thing the group collects towards — bereavement, insurance, administration, and so on.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Create a fund'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Fund')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),

                // A fund's name alone said nothing about whether it was in use.
                Tables\Columns\TextColumn::make('collections_count')
                    ->label('Members contributing')
                    ->counts('collections')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('collections_sum_amount')
                    ->label('Collected')
                    ->sum('collections', 'amount')
                    ->alignEnd()
                    ->weight('medium')
                    ->formatStateUsing(fn ($state) => Money::kes($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('payables_sum_total_amount')
                    ->label('Paid out')
                    ->sum('payables', 'total_amount')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => Money::kes($state))
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Delete these funds?')
                        ->modalDescription('Collections and payments already recorded against a deleted fund will lose the name they were filed under. Only delete a fund that was created in error.'),
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
