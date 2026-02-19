<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Existing inspire command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-run GDMS sync hourly
Schedule::command('gdms:sync-contacts')->hourly();
// or for testing, every five minutes:
// Schedule::command('gdms:sync-contacts')->everyFiveMinutes();
