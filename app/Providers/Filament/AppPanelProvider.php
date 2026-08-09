<?php

namespace App\Providers\Filament;

use App\Filament\Navigation;
use App\Filament\Resources\IncomeResource;
use App\Filament\Resources\LoanResource;
use App\Filament\Resources\PayableResource;
use App\Filament\Resources\ReceivableResource;
use App\Filament\Resources\UserResource;
use Awcodes\FilamentQuickCreate\QuickCreatePlugin;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentView;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('/')
            ->login()
            ->passwordReset()
            ->profile()
            ->brandName('Glof Finance')
            ->favicon(asset('favicon.ico'))

            /*
            | Colour carries meaning in a money app, so the palette is assigned
            | by role rather than by taste:
            |   primary — teal, a calm "this is the app" colour that does not
            |             compete with the red/green that amounts need;
            |   danger  — reserved exclusively for money owed and destructive
            |             actions, so a red badge always means "look at this";
            |   success — reserved for money received and settled debts.
            | The previous panel used orange for primary, which collided with
            | the warning colour and made every button look urgent.
            */
            ->colors([
                'primary' => Color::Teal,
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info' => Color::Blue,
                'gray' => Color::Slate,
            ])
            ->viteTheme('resources/css/app.css')

            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(MaxWidth::ScreenTwoExtraLarge)

            /*
            | Groups are declared here (rather than implicitly, from whatever
            | string a resource happens to set) so their order and icons are
            | deliberate and stable.
            */
            ->navigationGroups([
                NavigationGroup::make(Navigation::MONEY_IN)
                    ->icon('heroicon-o-arrow-down-tray'),
                NavigationGroup::make(Navigation::MONEY_OUT)
                    ->icon('heroicon-o-arrow-up-tray'),
                NavigationGroup::make(Navigation::LENDING)
                    ->icon('heroicon-o-hand-raised'),
                NavigationGroup::make(Navigation::PEOPLE)
                    ->icon('heroicon-o-user-group'),
                NavigationGroup::make(Navigation::REPORTS)
                    ->icon('heroicon-o-document-chart-bar'),
                NavigationGroup::make(Navigation::SETUP)
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            /*
            | Widgets are placed explicitly by the Dashboard page instead of
            | being auto-registered here. The default panel shipped Filament's
            | own "you are logged in" and version-branding widgets as the
            | entire home screen, which told the treasurer nothing.
            */
            ->widgets([])

            ->plugins([
                /*
                | One search affordance, not two. The panel previously loaded
                | both Spotlight and the global search modal, which bound
                | overlapping shortcuts and gave two different search UIs for
                | the same data. The modal wins because it searches records via
                | Filament's own global search.
                */
                GlobalSearchModalPlugin::make()
                    ->slideOver(),

                /*
                | Quick-create is limited to the things a treasurer genuinely
                | starts from scratch. Debts and repayments are consequences of
                | other actions, never something you "create", so offering them
                | here only invites bad data.
                */
                QuickCreatePlugin::make()
                    ->includes([
                        ReceivableResource::class,
                        PayableResource::class,
                        LoanResource::class,
                        IncomeResource::class,
                        UserResource::class,
                    ])
                    ->sort(false)
                    ->label('Record')
                    // Members do not record anything, so the button is not
                    // offered to them at all rather than opening onto nothing.
                    ->hidden(fn (): bool => ! auth()->user()?->isAdmin()),

                FilamentApexChartsPlugin::make(),
            ])

            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldSuffix('⌘K')
            ->spa()
            ->unsavedChangesAlerts()
            ->databaseTransactions()

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function register(): void
    {
        parent::register();

        FilamentView::registerRenderHook('panels::body.end', fn (): string => Blade::render("@vite('resources/js/app.js')"));
    }
}
