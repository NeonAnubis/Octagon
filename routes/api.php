<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\IntegrationStatusController;
use App\Http\Controllers\Api\MarketingController;
use App\Http\Controllers\Api\MarketingIntelligenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'overview']);

    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::get('/events/{id}/velocity', [EventController::class, 'velocity']);
    Route::get('/events/{id}/forecast', [EventController::class, 'forecast']);
    Route::get('/events/{id}/historical', [MarketingIntelligenceController::class, 'historicalComparison']);

    Route::get('/marketing', [MarketingController::class, 'index']);
    Route::get('/marketing/campaigns', [MarketingController::class, 'campaigns']);
    Route::get('/marketing/timing', [MarketingIntelligenceController::class, 'timing']);
    Route::get('/marketing/audience', [MarketingIntelligenceController::class, 'audience']);
    Route::get('/marketing/spend-optimization', [MarketingIntelligenceController::class, 'spendOptimization']);

    Route::get('/integrations', [IntegrationStatusController::class, 'index']);

    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/{id}/resolve', [AlertController::class, 'resolve']);
    Route::post('/alerts/{id}/read', [AlertController::class, 'markRead']);

    Route::post('/ai/chat', [AiController::class, 'chat']);
    Route::get('/ai/recommendations', [AiController::class, 'recommendations']);
});
