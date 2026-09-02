<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule hourly pruning of Telescope log entries (keeps a rolling 12-hour window of 100% full request logs)
Schedule::command('telescope:prune --hours=12')->hourly();
