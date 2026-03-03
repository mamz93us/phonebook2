<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('gdms:sync-contacts')->everyFiveMinutes();
Schedule::job(new \App\Jobs\RunNocAlertsJob)->everyFiveMinutes();
Schedule::job(new \App\Jobs\CheckLicenseMonitorsJob)->hourly();

// VPN Hub: Check tunnel status every minute
Schedule::job(new \App\Jobs\CheckVpnStatusJob)->everyMinute()->withoutOverlapping(5);

// Network Monitoring: Collect SNMP and Ping metrics every 5 minutes
Schedule::job(new \App\Jobs\CollectMetricsJob)->everyFiveMinutes()->withoutOverlapping(10);

// Identity Sync: runs twice daily at 06:00 and 18:00
Schedule::command('identity:sync')->twiceDaily(6, 18)
    ->withoutOverlapping(30) // prevent duplicate runs
    ->runInBackground();     // run in a separate process so scheduler isn't blocked
