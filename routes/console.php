<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('birthday:send-coupons --discount=20%')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('[Birthday Cron] Gửi email sinh nhật thành công lúc ' . now());
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('[Birthday Cron] Gửi email sinh nhật THẤT BẠI lúc ' . now());
    });
