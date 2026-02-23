<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use App\Models\Contact;
use App\Models\Branch;
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

        // ── Permission Gates ───────────────────────────────────
        // manage-users: create/edit/delete users (super_admin only)
        Gate::define('manage-users', fn($user) => $user->role === 'super_admin');

        // edit-content: create/edit/delete any data (super_admin + admin)
        Gate::define('edit-content', fn($user) => in_array($user->role, ['super_admin', 'admin']));

        // manage-settings: access settings page (super_admin + admin)
        Gate::define('manage-settings', fn($user) => in_array($user->role, ['super_admin', 'admin']));
    }
}
