<?php

namespace App\Filament\Resources;

use App\Enums\DebtStatusEnum;
use App\Enums\PaymentMode;
use App\Filament\Resources\DebtResource\Pages;
use App\Filament\Resources\DebtResource\RelationManagers;
use App\Models\Debt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class DebtResource extends Resource
{
    protected static ?string $model = Debt::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Liabilities';

    public static function getNavigationBadge(): ?string
    {
        return Debt::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Debt::getForm());
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Account')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        // Check if the 'account.name' is null or empty and return 'Credited Loan'
                        return $record->account->name ?? 'Credited Loan';
                    }),
                Tables\Columns\TextColumn::make('outstanding_balance')
                    ->prefix('KES ')
                    ->formatStateUsing(fn($state) => number_format($state, 2))
                    ->searchable(),
                Tables\Columns\TextColumn::make('debt_status')
                    ->label('Debt Status')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->color(function ($state) {
                        return $state->getColor();
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
                Tables\Filters\SelectFilter::make('debt_status')
                    ->label('Debt Status')
                    ->multiple()
                    ->searchable()
                    ->options(collect(DebtStatusEnum::cases())->mapWithKeys(function ($case) {
                        return [$case->value => ucwords(str_replace('_', ' ', $case->value))];
                    }))
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn($record) => $record->debt_status !== DebtStatusEnum::Cleared),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
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
            'index' => Pages\ListDebts::route('/'),
            'edit' => Pages\EditDebt::route('/{record}/edit'),
        ];
    }
}
