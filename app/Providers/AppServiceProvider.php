<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\DataSource;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Policies\AuditLogPolicy;
use App\Policies\DataSourcePolicy;
use App\Policies\HikingGoalPolicy;
use App\Policies\MountainPolicy;
use App\Policies\OfficialStatusPolicy;
use App\Policies\PermitRequirementPolicy;
use App\Policies\TrailConditionReportPolicy;
use App\Policies\TrailPolicy;
use App\Policies\TripPlanPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Laravel 13 ships no AuthServiceProvider, so policies are registered here.
        Gate::policy(TripPlan::class, TripPlanPolicy::class);
        Gate::policy(TrailConditionReport::class, TrailConditionReportPolicy::class);
        Gate::policy(HikingGoal::class, HikingGoalPolicy::class);
        Gate::policy(Mountain::class, MountainPolicy::class);
        Gate::policy(Trail::class, TrailPolicy::class);
        Gate::policy(DataSource::class, DataSourcePolicy::class);
        Gate::policy(OfficialStatus::class, OfficialStatusPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(PermitRequirement::class, PermitRequirementPolicy::class);
    }
}
