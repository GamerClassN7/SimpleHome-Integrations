<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('shellycloud')->group(function () {
    Route::middleware(['auth', 'verified', 'language'])->post('/{properti_id}/set/{value}', [Modules\ShellyCloud\Http\Controllers\ShellyCloudController::class, 'set'])->name('shellycloud.set');
    Route::middleware(['auth', 'verified', 'language'])->get('/{device_id}/reboot', [Modules\ShellyCloud\Http\Controllers\ShellyCloudController::class, 'reboot'])->name('shellycloud.devices.reboot');
});
