<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('sitepulse:dispatch-due-checks --limit=50')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command(
    'queue:work database --queue=monitoring,default --stop-when-empty --max-time=50 --tries=3 --timeout=40'
)
    ->everyMinute()
    ->withoutOverlapping(2);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
