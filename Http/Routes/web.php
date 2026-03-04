<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineInfoPulse\Http\Controllers\AirlineInfoPulseController;

Route::group([
    'as'         => 'airlineinfopulse.',
    'prefix'     => 'airline-info-pulse',
    'middleware'  => ['web', 'auth'],
], function () {
    Route::get('/', [AirlineInfoPulseController::class, 'index'])->name('index');
    Route::get('/guide', [AirlineInfoPulseController::class, 'guide'])->name('guide');
    Route::get('/compare', [AirlineInfoPulseController::class, 'comparePilot'])->name('compare')->middleware('throttle:30,1');
});
