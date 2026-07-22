<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Left commented out on purpose: App\Policies\UserPolicy already gets wired
        // to App\Models\User automatically, since it follows Laravel's policy
        // naming/location convention (Gate::guessPolicyName() replaces \Models\
        // with \Policies\ and appends "Policy"). Kept here as an explicit,
        // grep-able reference to the mechanism in case that convention ever
        // needs to be overridden (e.g. a non-standard model location/name).
        // Gate::policy(User::class, UserPolicy::class);
    }
}
