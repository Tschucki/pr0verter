<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\RenderWeeklyReportPngController;
use App\Http\Controllers\Admin\WeeklyReportPreviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalNoticeController;
use App\Http\Controllers\ListConverterController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\StartConverterController;
use App\Http\Controllers\StatController;
use App\Http\Controllers\ThumbnailController;
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

Route::get('conversions/thumbnail/{conversion}', ThumbnailController::class)
    ->name('conversions.thumbnail');

Route::patch('conversions/toggle-public/{conversion}', [ConversionController::class, 'togglePublicFlag'])
    ->name('conversions.toggle-public');

Route::patch('conversions/cancel/{conversion}', [ConversionController::class, 'cancel'])
    ->name('conversions.cancel');

Route::get('/login', [AuthController::class, 'index'])
    ->name('login');

Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('auth.logout');

Route::get('/impressum', LegalNoticeController::class)->name('legal-notice');
Route::get('/datenschutz', PrivacyPolicyController::class)->name('privacy-policy');

Route::middleware(['auth', 'can:admin'])->prefix('admin/weekly-report')->group(function () {
    Route::get('preview', WeeklyReportPreviewController::class)
        ->name('admin.weekly-report.preview');

    Route::post('render-png', RenderWeeklyReportPngController::class)
        ->middleware('throttle:weekly-report-render')
        ->name('admin.weekly-report.render-png');
});
