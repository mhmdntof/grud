<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\GenerateRecurringOrders;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// توليد الطلبات الدورية كل يوم الساعة 8 صباحاً
Schedule::command('recurring:generate')
    ->dailyAt('08:00');
