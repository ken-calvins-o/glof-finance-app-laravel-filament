<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LoanResource;
use App\Filament\Resources\PayableResource;
use App\Filament\Resources\ReceivableResource;
use App\Filament\Widgets\FundBalances;
use App\Filament\Widgets\GroupOverview;
use App\Filament\Widgets\MoneyFlowChart;
use App\Filament\Widgets\MyMoney;
use App\Filament\Widgets\WhoOwesUs;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * The home screen.
 *
 * The panel previously shipped Filament's stock dashboard: an account card
 * saying who you are logged in as, and a card advertising the Filament version.
 * Neither answers a single question a treasurer opens the app with.
 *
 * This one is built around the two jobs the home screen has: tell me where we
 * stand, and let me start today's work without hunting the sidebar for it.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Overview';

    protected static ?string $title = 'Overview';

    protected static ?int $navigationSort = -2;

    /**
     * A greeting that names the person and the period they are working in.
     * Small, but it orients you: a lot of data-entry errors in this kind of app
     * come from posting into the wrong month.
     */
    public function getSubheading(): ?string
    {
        $name = str(auth()->user()?->name ?? '')->before(' ');

        return trim(sprintf(
            '%s, %s. Today is %s.',
            $this->greeting(),
            $name,
            now()->format('l j F Y'),
        ));
    }

    protected function greeting(): string
    {
        return match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    /**
     * A link into the manual, offered from the screen everyone lands on.
     *
     * Built fresh each time it is asked for rather than stored: an Action is a
     * mutable object, and the same instance placed in two positions would carry
     * whatever the first position did to it.
     */
    protected function helpAction(): Action
    {
        return Action::make('help')
            ->label('How do I…?')
            ->icon('heroicon-o-lifebuoy')
            ->color('gray')
            ->url(Help::getUrl());
    }

    /**
     * The things a treasurer starts from the home screen, as buttons rather
     * than as a sidebar hunt. Members get none of them, because members do not
     * record money — but they do get the way into the manual, which is the one
     * thing a member on their first visit is most likely to want.
     */
    protected function getHeaderActions(): array
    {
        if (! auth()->user()?->isAdmin()) {
            return [$this->helpAction()->button()];
        }

        return [
            Action::make('recordCollection')
                ->label('Record money in')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(ReceivableResource::getUrl('create')),

            ActionGroup::make([
                Action::make('recordPayment')
                    ->label('Record a payment out')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->url(PayableResource::getUrl('create')),

                Action::make('issueLoan')
                    ->label('Issue a loan')
                    ->icon('heroicon-o-hand-raised')
                    ->url(LoanResource::getUrl('create')),

                $this->helpAction(),
            ])
                ->label('More')
                ->icon('heroicon-m-ellipsis-vertical')
                ->button()
                ->color('gray'),
        ];
    }

    public function getWidgets(): array
    {
        return [
            MyMoney::class,
            GroupOverview::class,
            MoneyFlowChart::class,
            WhoOwesUs::class,
            FundBalances::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
