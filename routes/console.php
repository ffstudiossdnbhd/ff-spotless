<?php

use App\Console\Commands\GenerateVapidKeysCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::starting(function ($artisan) {
    $artisan->resolve(GenerateVapidKeysCommand::class);
});
