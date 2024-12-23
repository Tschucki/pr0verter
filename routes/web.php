<?php

declare(strict_types=1);

use App\Http\Controllers\ConversionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListConverterController;
use App\Http\Controllers\StartConverterController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/login', function () {
    return redirect()->route('home');
})->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/conversions', [ListConverterController::class, 'index'])
        ->name('conversions.list');

    Route::middleware([VerifyCsrfToken::class])
        ->group(function () {
            Route::post('/conversions', [ListConverterController::class, 'myConversions'])
                ->name('conversions.my');
            Route::post('/converter/start', StartConverterController::class)
                ->name('converter.start')
                ->middleware(['throttle']);
        });

    Route::get('conversions/download/{conversion}', [ConversionController::class, 'download'])
        ->name('conversions.download');
});

