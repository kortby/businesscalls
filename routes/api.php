<?php

use App\Http\Controllers\Api\CallWebhookController;
use App\Http\Controllers\Api\DispatchWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/dispatch', DispatchWebhookController::class);
Route::post('/webhooks/call-events/{tenant_id?}', [CallWebhookController::class, 'handle'])->name('webhook.call-events');
