<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\DebtStatusEnum;
use App\Filament\Resources\UserResource;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Receivable;
use App\Models\User;
use App\Support\Money;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Collection;

/**
 * A member's statement.
 *
 * The app could tell you the whole group's position and it could show you a
 * raw ledger of every transaction ever recorded, but there was no screen that
 * answered "how does Jane stand?" — which is the single most common question
 * put to a treasurer. Answering it meant filtering four different tables by
 * name and adding up the results by eye.
 *
 * Everything here is read-only on purpose. It is the page you turn a laptop
 * around to show someone.
 */
class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getSubheading(): ?string
    {
        return 'Member statement · ' . $this->record->created_at?->format('\J\o\i\n\e\d F Y');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make()
                ->columns(['default' => 1, 'sm' => 4])
                ->schema([
                    ImageEntry::make('avatar')
                        ->hiddenLabel()
                        ->circular()
                        ->size(72)
                        ->state(fn (User $record) => $record->avatar_url)
                        ->columnSpan(1),

                    Grid::make(['default' => 2, 'lg' => 4])
                        ->columnSpan(['default' => 1, 'sm' => 3])
                        ->schema([
                            TextEntry::make('member_status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state?->getLabel())
                                ->icon(fn ($state) => $state?->getIcon())
                                ->color(fn ($state) => $state?->getColor()),

                            TextEntry::make('role')
                                ->label('Access level')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state?->getLabel())
                                ->color(fn ($state) => $state?->getColor()),

                            TextEntry::make('phone')
                                ->label('Phone')
                                ->placeholder('Not recorded')
                                ->copyable(),

                            TextEntry::make('email')
                                ->label('Email')
                                ->placeholder('Not recorded')
                                ->copyable(),
                        ]),
                ]),

            Section::make('Where they stand')
                ->description('Everything below is worked out from the transactions recorded in the app.')
                ->icon('heroicon-o-scale')
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                ->schema([
                    TextEntry::make('savings_balance')
                        ->label('Savings')
                        ->state(fn (User $record) => Money::kes($record->savings_balance))
                        ->weight('semibold')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->helperText('Cash they can draw on'),

                    TextEntry::make('net_worth')
                        ->label('Net worth')
                        ->state(fn (User $record) => Money::kes($record->net_worth))
                        ->weight('semibold')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->color(fn (User $record) => $record->net_worth < 0 ? 'danger' : null)
                        ->helperText('Put in, less drawn out'),

                    TextEntry::make('total_contributed')
                        ->label('Contributed')
                        ->state(fn (User $record) => Money::kes($record->total_contributed))
                        ->weight('semibold')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->helperText('Across every fund'),

                    TextEntry::make('outstanding_debt')
                        ->label('Owes the group')
                        ->state(fn (User $record) => Money::kes($record->outstanding_debt))
                        ->weight('semibold')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->color(fn (User $record) => $record->outstanding_debt > 0 ? 'danger' : 'success')
                        ->helperText(fn (User $record) => $record->outstanding_debt > 0
                            ? 'Loans and arrears'
                            : 'Nothing outstanding'),
                ]),

            Section::make('Contributions by fund')
                ->icon('heroicon-o-rectangle-group')
                ->schema([
                    ViewEntry::make('funds')
                        ->hiddenLabel()
                        ->view('filament.infolists.member-funds')
                        ->viewData(fn (User $record) => ['funds' => $this->fundBreakdown($record)]),
                ]),

            Section::make('Money owed')
                ->icon('heroicon-o-exclamation-circle')
                ->visible(fn (User $record) => $this->debts($record)->isNotEmpty())
                ->schema([
                    ViewEntry::make('debts')
                        ->hiddenLabel()
                        ->view('filament.infolists.member-debts')
                        ->viewData(fn (User $record) => ['debts' => $this->debts($record)]),
                ]),

            Section::make('Recent activity')
                ->description('The last ten payments recorded against this member.')
                ->icon('heroicon-o-clock')
                ->collapsible()
                ->schema([
                    ViewEntry::make('activity')
                        ->hiddenLabel()
                        ->view('filament.infolists.member-activity')
                        ->viewData(fn (User $record) => ['entries' => $this->recentCollections($record)]),
                ]),
        ]);
    }

    /**
     * @return Collection<int, array{name: string, amount: string}>
     */
    protected function fundBreakdown(User $record): Collection
    {
        return $record->accountCollections()
            ->with('account')
            ->get()
            ->sortByDesc('amount')
            ->map(fn ($collection) => [
                'name' => $collection->account?->name ?? 'Unknown fund',
                'amount' => Money::kes($collection->amount),
                'raw' => (float) $collection->amount,
            ])
            ->values();
    }

    /**
     * @return Collection<int, Debt>
     */
    protected function debts(User $record): Collection
    {
        return $record->debts()
            ->with('account')
            ->whereIn('debt_status', DebtStatusEnum::outstandingValues())
            ->where('outstanding_balance', '>', 0)
            ->orderByDesc('outstanding_balance')
            ->get();
    }

    /**
     * @return Collection<int, Receivable>
     */
    protected function recentCollections(User $record): Collection
    {
        return $record->receivables()
            ->with('account')
            ->latest('created_at')
            ->limit(10)
            ->get();
    }
}
