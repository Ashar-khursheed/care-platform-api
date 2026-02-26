<?php

use App\Jobs\AutoReleaseWithdrawalJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// =============================================
// AUTO-RELEASE WITHDRAWAL ESCROW JOB
// Runs daily at midnight — finds all escrow records
// where auto_release_at <= now() and releases them
// automatically if admin has not acted.
// =============================================
Schedule::job(new AutoReleaseWithdrawalJob)->dailyAt('00:00')
    ->name('auto-release-withdrawals')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::critical('AutoReleaseWithdrawalJob scheduler failed.');
    });

