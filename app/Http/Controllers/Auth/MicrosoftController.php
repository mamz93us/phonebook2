<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftController extends Controller
{
    /**
     * Configure Socialite dynamically from DB settings and redirect to Microsoft.
     */
    public function redirect()
    {
        $this->configureSocialite();

        return Socialite::driver('microsoft')->redirect();
    }

    /**
     * Handle the Microsoft OAuth callback.
     */
    public function callback()
    {
        $this->configureSocialite();

        try {
            $msUser = Socialite::driver('microsoft')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Microsoft login failed: ' . $e->getMessage());
        }

        // Find or create the user
        $user = User::firstOrCreate(
            ['email' => $msUser->getEmail()],
            [
                'name'              => $msUser->getName() ?? $msUser->getEmail(),
                'password'          => \Illuminate\Support\Str::random(32), // unusable random password
                'role'              => Setting::get()->sso_default_role ?? 'viewer',
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user, true);

        // Use route() directly — intended() can point back to /login if user
        // navigated there manually, causing an infinite redirect loop.
        return redirect()->route('admin.dashboard');
    }

    private function configureSocialite(): void
    {
        $settings = Setting::get();

        Config::set('services.microsoft', [
            'client_id'     => $settings->sso_client_id,
            'client_secret' => $settings->sso_client_secret,
            'redirect'      => url('/auth/microsoft/callback'),
            'tenant'        => $settings->sso_tenant_id ?? 'common',
        ]);
    }
}
