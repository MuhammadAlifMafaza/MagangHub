<?php

namespace App\Providers;

use App\Models\Assessment;
use App\Models\ReportVersion;
use App\Models\WeeklyReport;
use App\Policies\AssessmentPolicy;
use App\Policies\WeeklyReportPolicy;
use Carbon\CarbonImmutable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Table::configureUsing(static function (Table $table): void {
            $table
                ->striped()
                ->filtersLayout(FiltersLayout::AboveContentCollapsible)
                ->paginationPageOptions([10, 25, 50]);
        });
        Gate::policy(WeeklyReport::class, WeeklyReportPolicy::class);
        Gate::policy(ReportVersion::class, WeeklyReportPolicy::class);
        Gate::policy(Assessment::class, AssessmentPolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
