<?php

use App\Http\Controllers\Webhooks\FineTunerWebhookController;
use App\Http\Controllers\Webhooks\TwilioVoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('twilio.signature')->group(function () {
    Route::post('/twilio/voice/incoming', [TwilioVoiceController::class, 'incoming']);
    Route::post('/twilio/voice/status', [TwilioVoiceController::class, 'status']);
    Route::post('/twilio/recording', [TwilioVoiceController::class, 'recording']);
});

Route::post('/finetuner/inbound', [FineTunerWebhookController::class, 'inbound']);
Route::post('/finetuner/post-call', [FineTunerWebhookController::class, 'postCall']);
Route::post('/synthflow/inbound', [FineTunerWebhookController::class, 'inbound']);
Route::post('/synthflow/post-call', [FineTunerWebhookController::class, 'postCall']);
