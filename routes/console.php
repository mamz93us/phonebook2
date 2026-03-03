<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ──────────────────────────────────────────────────────────────────────
// Load intervals from DB (with safe defaults if not yet configured).
// ──────────────────────────────────────────────────────────────────────
$settings = \App\Models\Setting::first();
$gdmsInterval     = $settings?->gdms_sync_interval     ?: 5;     // default 5 min
$merakiInterval   = $settings?->meraki_polling_interval ?: 5;     // default 5 min
$identityInterval = $settings?->identity_sync_interval  ?: 720;   // default 12 h (720 min)

// GDMS Contact Sync
Schedule::command('gdms:sync-contacts')
    ->everyMinutes($gdmsInterval)
    ->withoutOverlapping(15)
    ->runInBackground();

// Meraki Network Sync
Schedule::command('meraki:sync')
    ->everyMinutes($merakiInterval)
    ->withoutOverlapping(10)
    ->runInBackground();

// Azure / Entra ID Identity Sync
Schedule::command('identity:sync')
    ->everyMinutes($identityInterval)
    ->withoutOverlapping(30)
    ->runInBackground();

// Other internal jobs
Schedule::job(new \App\Jobs\RunNocAlertsJob)->everyFiveMinutes();
Schedule::job(new \App\Jobs\CheckLicenseMonitorsJob)->hourly();
Schedule::job(new \App\Jobs\CheckVpnStatusJob)->everyMinute()->withoutOverlapping(5);
Schedule::job(new \App\Jobs\CollectMetricsJob)->everyFiveMinutes()->withoutOverlapping(10);
