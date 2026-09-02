<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily 24-hour automatic pruning of Telescope log entries to keep DB lightweight
Schedule::command('telescope:prune --hours=24')->daily();
