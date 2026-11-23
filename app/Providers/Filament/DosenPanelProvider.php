<?php

namespace App\Providers\Filament;

use App\Filament\Auth\StaffLogin;
use App\Filament\Dosen\Pages\Profile;
use App\Filament\Dosen\Pages\Settings;
use App\Filament\Dosen\Widgets\LecturerGuidanceStats;
use App\Filament\Dosen\Widgets\PendingLecturerReports;
use App\Filament\Pages\ReportDetails;
use App\Http\Middleware\RequirePasswordChange;
use Filament\Actions\Action;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DosenPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dosen')
            ->path('dosen')
            ->defaultThemeMode(ThemeMode::System)
            ->brandName('MagangHub')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandLogo(asset('images/brand/MagangHub-Logo-tag.svg'))
            ->darkModeBrandLogo(asset('images/brand/MagangHub-Logo-tag-dark.svg'))
            ->brandLogoHeight('3.1rem')
            ->favicon(asset('images/brand/Logo.svg'))
            ->login(StaffLogin::class)
            ->strictAuthorization()
            ->databaseNotifications()
            ->navigationGroups([
                NavigationGroup::make()->label('Pembimbingan'),
                NavigationGroup::make()->label('Laporan'),
            ])
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $action
                    ->label('Profil')
                    ->url(fn (): string => Profile::getUrl(panel: 'dosen'))
                    ->icon('heroicon-o-user-circle'),
                'settings' => Action::make('settings')
                    ->label('Pengaturan')
                    ->url(fn (): string => Settings::getUrl(panel: 'dosen'))
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn (): View => view('filament.components.user-menu-name'))
            ->colors([
                'primary' => Color::hex('#1C3155'),
                'success' => Color::hex('#15803D'),
                'warning' => Color::hex('#B45309'),
                'danger' => Color::hex('#B91C1C'),
                'info' => Color::hex('#0369A1'),
                'gray' => Color::hex('#334155'),
            ])
            ->discoverResources(in: app_path('Filament/Dosen/Resources'), for: 'App\Filament\Dosen\Resources')
            ->discoverPages(in: app_path('Filament/Dosen/Pages'), for: 'App\Filament\Dosen\Pages')
            ->pages([
                Dashboard::class,
                ReportDetails::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Dosen/Widgets'), for: 'App\Filament\Dosen\Widgets')
            ->widgets([
                LecturerGuidanceStats::class,
                PendingLecturerReports::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                RequirePasswordChange::class,
            ]);
    }
}
