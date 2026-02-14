<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Models\Loan;
use App\Enums\DebtStatusEnum;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    public static function getNavigationBadge(): ?string
    {
        return Loan::count();
    }

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-end-on-rectangle';
    protected static ?string $navigationGroup = 'Liabilities';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Loan::getForm());
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
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->prefix('Kes ')
                    ->formatStateUsing(fn($state) => number_format($state, 2))
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->prefix('Kes ')
                    ->getStateUsing(function ($record) {
                        // Prefer eager-loaded debts when available
                        $debt = null;

                        if (isset($record->user) && isset($record->user->debts) && $record->user->debts->count()) {
                            $debt = $record->user->debts->first();
                        } else {
                            // Fallback to direct query: find the most recent Debt for the user with no account ("Credited Loan")
                            $debt = \App\Models\Debt::where('user_id', $record->user_id)
                                ->whereNull('account_id')
                                ->orderByDesc('created_at')
                                ->first();
                        }

                        $amount = $debt ? $debt->outstanding_balance : ($record->balance ?? 0);

                        // Ensure non-negative and return formatted string (state expected to be raw value; we'll format here)
                        $amount = max(0, $amount);

                        return number_format($amount, 2);
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('interest')
                    ->searchable()
                    ->label('Interest P.M.%'),
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
                Tables\Filters\SelectFilter::make('users')
                    ->relationship('user', 'name')
                    ->label('Members')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn($record) => $record->debt_status !== DebtStatusEnum::Cleared),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // Eager-load user's credited-loan debts to avoid N+1 queries when rendering the table
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user.debts' => function ($q) {
            $q->whereNull('account_id')->orderByDesc('created_at');
        }]);
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
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
