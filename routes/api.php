<?php

use App\Http\Controllers\Api\V1\Admin\AdminCrawlerNodeController;
use App\Http\Controllers\Api\V1\Admin\AdminCrawlJobController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminDomainController;
use App\Http\Controllers\Api\V1\Crawler\CrawlerHeartbeatController;
use App\Http\Controllers\Api\V1\Crawler\CrawlerJobClaimController;
use App\Http\Controllers\Api\V1\Crawler\CrawlerJobFailureController;
use App\Http\Controllers\Api\V1\Crawler\CrawlerJobResultController;
use App\Http\Controllers\Api\V1\Crawler\CrawlerRegistrationController;
use App\Http\Controllers\Api\V1\Public\CompanySearchController;
use App\Http\Controllers\Api\V1\Public\ContactSearchController;
use App\Http\Controllers\Api\V1\Public\DomainLookupController;
use App\Http\Controllers\Api\V1\Public\DomainRegistrationController;
use App\Http\Controllers\Api\V1\Public\TechnologyLookupController;
use App\Http\Middleware\AuthenticateCrawler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Distributed Crawler Control Plane API v1
Route::prefix('v1/crawler')->group(function () {
    Route::post('/register', [CrawlerRegistrationController::class, 'register']);

    Route::middleware(AuthenticateCrawler::class)->group(function () {
        Route::post('/heartbeat', [CrawlerHeartbeatController::class, 'heartbeat']);
        Route::post('/jobs/claim', [CrawlerJobClaimController::class, 'claim']);
        Route::post('/jobs/{job}/result', [CrawlerJobResultController::class, 'result']);
        Route::post('/jobs/{job}/failed', [CrawlerJobFailureController::class, 'failed']);
    });
});

// Admin Control Plane Dashboard APIs v1
Route::prefix('v1/admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/domains', [AdminDomainController::class, 'index']);
    Route::get('/crawlers', [AdminCrawlerNodeController::class, 'index']);
    Route::get('/jobs', [AdminCrawlJobController::class, 'index']);
});

// Public Intelligence REST API v1
Route::prefix('v1')->group(function () {
    Route::post('/domains', [DomainRegistrationController::class, 'register']);
    Route::get('/domains/{domain}', [DomainLookupController::class, 'show']);
    Route::get('/companies/search', [CompanySearchController::class, 'index']);
    Route::get('/contacts/search', [ContactSearchController::class, 'index']);
    Route::get('/technologies/lookup', [TechnologyLookupController::class, 'index']);
});
