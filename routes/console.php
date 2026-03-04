<?php

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
$gdmsInterval     = max(1, (int) ($settings?->gdms_sync_interval     ?: 5));
$merakiInterval   = max(1, (int) ($settings?->meraki_polling_interval ?: 5));
$identityInterval = max(1, (int) ($settings?->identity_sync_interval  ?: 720));

// Helper: build cron expression for "every N minutes"
$everyN = fn(int $n): string => $n === 1 ? '* * * * *' : "*/{$n} * * * *";

// GDMS Contact Sync
Schedule::command('gdms:sync-contacts')
    ->cron($everyN($gdmsInterval))
    ->withoutOverlapping(15)
    ->runInBackground();

// Meraki Network Sync
Schedule::command('meraki:sync')
    ->cron($everyN($merakiInterval))
    ->withoutOverlapping(10)
    ->runInBackground();

// Azure / Entra ID Identity Sync
Schedule::command('identity:sync')
    ->cron($everyN($identityInterval))
    ->withoutOverlapping(30)
    ->runInBackground();

// Other internal jobs
Schedule::job(new \App\Jobs\RunNocAlertsJob)->everyFiveMinutes();
Schedule::job(new \App\Jobs\CheckLicenseMonitorsJob)->hourly();
Schedule::job(new \App\Jobs\CheckVpnStatusJob)->everyMinute()->withoutOverlapping(5);
Schedule::job(new \App\Jobs\CheckHostAvailabilityJob)->everyMinute()->withoutOverlapping(2);
Schedule::job(new \App\Jobs\CollectSnmpMetricsJob)->everyMinute()->withoutOverlapping(2);
