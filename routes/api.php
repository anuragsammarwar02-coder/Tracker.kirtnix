<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Api\TrackingApiController;

/*
|--------------------------------------------------------------------------
| API Routes & Telegram Webhooks
|--------------------------------------------------------------------------
*/

// 1. Telegram Webhooks
Route::post('/telegram/webhook/{secret}', [TelegramWebhookController::class, 'handle'])->name('api.telegram.webhook');

// 2. High-Speed Deterministic Tracking API (Kirtnix, Vercel, Netlify, Custom)
Route::post('/track/view', [TrackingApiController::class, 'recordView'])->name('api.track.view');
Route::post('/track/invite', [TrackingApiController::class, 'getInvite'])->name('api.track.invite');
Route::post('/track/click', [TrackingApiController::class, 'recordClick'])->name('api.track.click');

// 3. Live Metrics API
Route::get('/analytics/{slug}/live-metrics', [\App\Http\Controllers\AnalyticsController::class, 'liveMetrics'])->name('api.analytics.live_metrics');

// 4. Conversion Meta CAPI Retry
Route::post('/conversions/{conversion}/retry-meta', [TrackingApiController::class, 'retryMeta'])->name('api.conversions.retry_meta');
