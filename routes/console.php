<?php

use Illuminate\Foundation\Inspiring;
use App\Models\SystemError;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    $retentionDays = max((int) env('SYSTEM_ERROR_RETENTION_DAYS', 90), 1);

    SystemError::query()
        ->where('created_at', '<', now()->subDays($retentionDays))
        ->delete();
})->daily()->name('system-errors-prune');
