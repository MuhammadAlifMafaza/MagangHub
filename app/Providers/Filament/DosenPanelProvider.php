<?php

namespace App\Providers\Filament;

use App\Filament\Dosen\Widgets\LecturerGuidanceStats;
use App\Filament\Dosen\Widgets\PendingLecturerReports;
use App\Http\Middleware\RequirePasswordChange;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
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
            ->brandName('MagangHub')
            ->login()
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
            ])
            ->discoverWidgets(in: app_path('Filament/Dosen/Widgets'), for: 'App\Filament\Dosen\Widgets')
            ->widgets([
                AccountWidget::class,
                LecturerGuidanceStats::class,
                PendingLecturerReports::class,
                FilamentInfoWidget::class,
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
