<?php

use App\Http\Controllers\Api\BookingStatusController;
use App\Http\Controllers\Api\CallWebhookController;
use App\Http\Controllers\Api\DispatchWebhookController;
use App\Http\Controllers\Api\IvrController;
use App\Http\Controllers\Api\McpController;
use App\Http\Controllers\Api\PronunciationDictionaryController;
use App\Http\Controllers\Api\SmsWebhookController;
use App\Http\Controllers\Api\WebCallController;
use App\Http\Middleware\RestrictToTelephonyIps;
use App\Http\Middleware\ThrottleTenantTelephony;
use App\Http\Middleware\WebhookGatewayMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/dispatch', DispatchWebhookController::class)->middleware([WebhookGatewayMiddleware::class, RestrictToTelephonyIps::class]);
Route::post('/webhooks/call-events/{tenant_id?}', [CallWebhookController::class, 'handle'])->name('webhook.call-events')->middleware(RestrictToTelephonyIps::class);
Route::post('/webhooks/sms/{tenant_id?}', [SmsWebhookController::class, 'handle'])->name('webhook.sms')->middleware(RestrictToTelephonyIps::class);
Route::post('/webhooks/ivr/{tenant_id?}', [IvrController::class, 'handle'])->name('webhook.ivr')->middleware(RestrictToTelephonyIps::class);
Route::match(['get', 'post'], '/mcp', [McpController::class, 'handle'])->name('mcp.server');
Route::post('/web-calls/token', [WebCallController::class, 'token'])->middleware(['auth:sanctum', ThrottleTenantTelephony::class]);
Route::put('/bookings/{booking}/status', [BookingStatusController::class, 'update'])->middleware('auth:sanctum');
Route::post('/settings/dictionary', [PronunciationDictionaryController::class, 'store'])->middleware('auth:sanctum');
