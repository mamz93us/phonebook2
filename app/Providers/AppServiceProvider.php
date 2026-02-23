<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use App\Models\Contact;
use App\Models\Branch;
use App\Models\RolePermission;
use App\Observers\ContactObserver;
use App\Observers\BranchObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register observers
        Contact::observe(ContactObserver::class);
        Branch::observe(BranchObserver::class);

        // Register Microsoft Socialite provider
        Event::listen(
            \SocialiteProviders\Manager\SocialiteWasCalled::class,
            \SocialiteProviders\Microsoft\MicrosoftExtendSocialite::class.'@handle'
        );

        // ── Permission Gates (DB-driven via role_permissions table) ──
        // Each gate maps to a permission slug. Falls back gracefully if
        // the table doesn't exist yet (before migration runs).
        $gateCheck = function ($user, string $permission): bool {
            try {
                return RolePermission::roleHas($user->role ?? '', $permission);
            } catch (\Exception) {
                return false;
            }
        };

        // Register a gate for every known permission slug
        foreach (RolePermission::allSlugs() as $slug) {
            Gate::define($slug, fn($user) => $gateCheck($user, $slug));
        }

        // Legacy aliases for existing @can() calls in blade templates
        Gate::define('edit-content', fn($user) => $gateCheck($user, 'manage-contacts'));
    }
}
