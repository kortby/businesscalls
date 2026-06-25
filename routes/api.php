<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\BookingStatusController;
use App\Http\Controllers\Api\CallFlowController;
use App\Http\Controllers\Api\CallRedactionController;
use App\Http\Controllers\Api\CallWebhookController;
use App\Http\Controllers\Api\DispatchWebhookController;
use App\Http\Controllers\Api\IvrController;
use App\Http\Controllers\Api\McpController;
use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\Api\PronunciationDictionaryController;
use App\Http\Controllers\Api\SandboxToggleController;
use App\Http\Controllers\Api\SmsWebhookController;
use App\Http\Controllers\Api\SpecializedKeywordsController;
use App\Http\Controllers\Api\TelephonyFallbackController;
use App\Http\Controllers\Api\WebCallController;
use App\Http\Controllers\Api\WebRtcTelemetryController;
use App\Http\Middleware\BlockSuspendedTenantCalls;
use App\Http\Middleware\EnsureWebhookIdempotency;
use App\Http\Middleware\RestrictToTelephonyIps;
use App\Http\Middleware\ThrottleTenantTelephony;
use App\Http\Middleware\TrafficRouterMiddleware;
use App\Http\Middleware\VerifyOAuthWebhookToken;
use App\Http\Middleware\WebhookGatewayMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/oauth/token', [OAuthController::class, 'token'])->name('oauth.token');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/dispatch', DispatchWebhookController::class)->middleware([VerifyOAuthWebhookToken::class, WebhookGatewayMiddleware::class, RestrictToTelephonyIps::class, EnsureWebhookIdempotency::class]);
Route::post('/webhooks/call-events/{tenant_id?}', [CallWebhookController::class, 'handle'])->name('webhook.call-events')->middleware([BlockSuspendedTenantCalls::class, RestrictToTelephonyIps::class, EnsureWebhookIdempotency::class]);
Route::post('/webhooks/sms/{tenant_id?}', [SmsWebhookController::class, 'handle'])->name('webhook.sms')->middleware([RestrictToTelephonyIps::class, EnsureWebhookIdempotency::class]);
Route::post('/webhooks/ivr/{tenant_id?}', [IvrController::class, 'handle'])->name('webhook.ivr')->middleware([RestrictToTelephonyIps::class, EnsureWebhookIdempotency::class]);
Route::post('/webhooks/ivr-keypress/{tenant_id?}', [IvrController::class, 'handle'])->name('webhook.ivr-keypress')->middleware([RestrictToTelephonyIps::class, EnsureWebhookIdempotency::class]);
Route::match(['get', 'post'], '/mcp', [McpController::class, 'handle'])->name('mcp.server');
Route::post('/web-calls/token', [WebCallController::class, 'token'])->middleware(['auth:sanctum', ThrottleTenantTelephony::class, TrafficRouterMiddleware::class]);
Route::post('/web-calls/refresh-token', [WebCallController::class, 'refreshToken'])->middleware('auth:sanctum');
Route::put('/bookings/{booking}/status', [BookingStatusController::class, 'update'])->middleware('auth:sanctum');
Route::post('/settings/dictionary', [PronunciationDictionaryController::class, 'store'])->middleware('auth:sanctum');
Route::post('/call-logs/{callLog}/redact', [CallRedactionController::class, 'redact'])->middleware('auth:sanctum');
Route::post('/web-calls/barge', [WebCallController::class, 'barge'])->middleware('auth:sanctum');
Route::post('/web-calls/whisper', [WebCallController::class, 'whisper'])->middleware('auth:sanctum');
Route::post('/settings/toggle-sandbox', [SandboxToggleController::class, 'toggle'])->middleware('auth:sanctum');
Route::post('/settings/specialized-keywords', [SpecializedKeywordsController::class, 'store'])->middleware('auth:sanctum');
Route::get('/settings/specialized-keywords', [SpecializedKeywordsController::class, 'index'])->middleware('auth:sanctum');
Route::post('/settings/call-flow', [CallFlowController::class, 'store'])->middleware('auth:sanctum');

Route::post('/telephony/fallback-route/{tenant_id?}', [TelephonyFallbackController::class, 'handle'])
    ->name('telephony.fallback-route');
Route::post('/settings/branded-caller-id', [AdminController::class, 'submitBrandedCallerId'])->middleware('auth:sanctum');

Route::post('/telemetry/webrtc', WebRtcTelemetryController::class)->middleware('auth:sanctum');
Route::post('/telemetry/quality-degraded', [WebRtcTelemetryController::class, 'degraded'])->middleware('auth:sanctum');
