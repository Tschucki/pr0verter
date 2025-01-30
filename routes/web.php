<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalNoticeController;
use App\Http\Controllers\ListConverterController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\StartConverterController;
use App\Http\Controllers\StatController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/conversions', [ListConverterController::class, 'index'])
    ->name('conversions.list');

Route::get('/stats', StatController::class)->name('stats');

Route::post('/conversions', [ListConverterController::class, 'myConversions'])
    ->name('conversions.my');
Route::post('/converter/start', StartConverterController::class)
    ->name('converter.start')
    ->middleware(['throttle']);

Route::get('conversions/download/{conversion}', [ConversionController::class, 'download'])
    ->name('conversions.download');

Route::patch('conversions/toggle-public/{conversion}', [ConversionController::class, 'togglePublicFlag'])
    ->name('conversions.toggle-public');

Route::get('/login', [AuthController::class, 'index'])
    ->name('login');

Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('auth.logout');

Route::get('/impressum', LegalNoticeController::class)->name('legal-notice');
Route::get('/datenschutz', PrivacyPolicyController::class)->name('privacy-policy');
