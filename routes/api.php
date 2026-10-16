<?php

use App\Http\Controllers\Api\AiToolController;
use App\Http\Controllers\Api\AppointmentApiController;
use App\Http\Controllers\Api\CallApiController;
use App\Http\Controllers\Api\FineTunerToolController;
use App\Http\Controllers\Api\LeadApiController;
use App\Http\Controllers\Api\ServiceApiController;
use Illuminate\Support\Facades\Route;

Route::get('/services', [ServiceApiController::class, 'index']);
Route::get('/services/{service}/availability', [ServiceApiController::class, 'availability']);

Route::post('/appointments/availability', [AppointmentApiController::class, 'availability']);
Route::post('/appointments', [AppointmentApiController::class, 'store']);
Route::patch('/appointments/{appointment}', [AppointmentApiController::class, 'update']);
Route::post('/appointments/{appointment}/cancel', [AppointmentApiController::class, 'cancel']);

Route::get('/leads', [LeadApiController::class, 'index']);
Route::post('/leads', [LeadApiController::class, 'store']);
Route::patch('/leads/{lead}', [LeadApiController::class, 'update']);

Route::get('/calls', [CallApiController::class, 'index']);

Route::middleware('voice.gateway')->group(function () {
    Route::get('/ai/context', [AiToolController::class, 'context']);
    Route::post('/ai/tools/invoke', [AiToolController::class, 'invoke']);
    Route::post('/ai/calls/complete', [AiToolController::class, 'completeCall']);
    Route::post('/ai/finetuner/tools/{tool}', [FineTunerToolController::class, 'invoke']);
    Route::post('/ai/synthflow/tools/{tool}', [FineTunerToolController::class, 'invoke']);
});
