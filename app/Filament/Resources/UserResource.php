<?php

namespace App\Filament\Resources;

use App\Enums\MemberStatus;
use App\Enums\RoleEnum;
use App\Filament\Concerns\AdminOnly;
use App\Filament\Navigation;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\Money;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class UserResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $activeNavigationIcon = 'heroicon-s-user-group';

    protected static ?string $navigationGroup = Navigation::PEOPLE;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'member';

    protected static ?string $pluralModelLabel = 'members';

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Members needing attention, not the roll count. The number of people in
     * the group is stable and unremarkable; the number who are suspended or
     * inactive is something to act on.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->where('member_status', '!=', MemberStatus::Active->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Members who are inactive or suspended';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Savings' => Money::kes($record->savings_balance),
            'Status' => $record->member_status?->getLabel() ?? '—',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema(User::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->persistFiltersInSession()
            ->filtersTriggerAction(fn ($action) => $action->button()->label('Filter'))
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading('No members yet')
            ->emptyStateDescription('Add the people in your group. Their joining fee is recorded as group income automatically.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Add a member'),
            ])
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(36)
                    ->getStateUsing(fn (User $record) => $record->avatar_url),

                Tables\Columns\TextColumn::make('name')
                    ->label('Member')
                    ->weight('medium')
                    // Contact details belong with the name, not in two more
                    // columns that push the money off the right of the screen.
                    ->description(fn (User $record) => collect([$record->phone, $record->email])
                        ->filter()
                        ->implode(' · ') ?: null)
                    ->searchable(['name', 'email', 'phone'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('savings_balance')
                    ->label('Savings')
                    ->alignEnd()
                    ->getStateUsing(fn (User $record) => $record->savings_balance)
                    ->formatStateUsing(fn ($state) => Money::kes($state)),

                Tables\Columns\TextColumn::make('net_worth')
                    ->label('Net worth')
                    ->alignEnd()
                    ->weight('medium')
                    ->getStateUsing(fn (User $record) => $record->net_worth)
                    ->formatStateUsing(fn ($state) => Money::kes($state))
                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : null),

                Tables\Columns\TextColumn::make('outstanding_debt')
                    ->label('Owes')
                    ->alignEnd()
                    ->getStateUsing(fn (User $record) => $record->outstanding_debt)
                    ->formatStateUsing(fn ($state) => (float) $state > 0 ? Money::kes($state) : '—')
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('member_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->icon(fn ($state) => $state?->getIcon())
                    ->color(fn ($state) => $state?->getColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Access')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->color(fn ($state) => $state?->getColor())
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('member_status')
                    ->label('Status')
                    ->options(MemberStatus::options())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('role')
                    ->label('Access level')
                    ->options(RoleEnum::options()),

                Tables\Filters\Filter::make('owing')
                    ->label('Owes the group money')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereHas(
                        'debts',
                        fn (Builder $q) => $q->where('outstanding_balance', '>', 0),
                    )),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Statement')
                    ->icon('heroicon-o-document-text'),
                Tables\Actions\EditAction::make()->label('Edit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Download as Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename(date('Y-m-d') . ' - Members')
                                ->fromTable()
                                ->askForFilename()
                                ->except('avatar'),
                        ]),
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Remove these members?')
                        ->modalDescription('Their savings, contributions and loan history go with them. If someone has simply stopped contributing, set their status to Inactive instead — that keeps the records intact.'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
