<?php

use App\Http\Controllers\Web\AdminDashboardWebController;
use App\Http\Controllers\Web\AdminDomainDetailWebController;
use App\Http\Controllers\Web\AdminGoogleKeyWebController;
use App\Http\Controllers\Web\AdminSystemControlWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Admin Web Control Plane Dashboard
Route::prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardWebController::class, 'index']);
    Route::get('/domains', [AdminDashboardWebController::class, 'domains']);
    Route::post('/domains', [AdminDashboardWebController::class, 'storeDomain']);
    Route::get('/domains/{id}', [AdminDomainDetailWebController::class, 'show']);
    Route::post('/domains/{id}/crawl', [AdminDomainDetailWebController::class, 'triggerCrawl']);
    Route::get('/crawlers', [AdminDashboardWebController::class, 'crawlers']);
    Route::get('/jobs', [AdminDashboardWebController::class, 'jobs']);

    // System Info & Global Crawl Engine Control
    Route::get('/system', [AdminSystemControlWebController::class, 'index']);
    Route::post('/system/crawl-control', [AdminSystemControlWebController::class, 'control']);

    // Google API Keys Pool Management
    Route::get('/google-keys', [AdminGoogleKeyWebController::class, 'index']);
    Route::post('/google-keys', [AdminGoogleKeyWebController::class, 'store']);
    Route::put('/google-keys/{id}', [AdminGoogleKeyWebController::class, 'update']);
    Route::post('/google-keys/{id}/reset', [AdminGoogleKeyWebController::class, 'resetQuota']);
    Route::post('/google-keys/reset-all', [AdminGoogleKeyWebController::class, 'resetAllQuotas']);
    Route::delete('/google-keys/{id}', [AdminGoogleKeyWebController::class, 'destroy']);
});
