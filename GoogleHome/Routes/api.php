<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('googlehome')->group(function () {
    Route::middleware(['auth:oauth'])->post('/fulfillment', [Modules\GoogleHome\Http\Controllers\GoogleHomeController::class, 'fulfillment'])->name('googlehome.fulfillment');
});
