<?php

use App\Http\Controllers\Webhooks\TwilioVoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('twilio.signature')->group(function () {
    Route::post('/twilio/voice/incoming', [TwilioVoiceController::class, 'incoming']);
    Route::post('/twilio/voice/status', [TwilioVoiceController::class, 'status']);
    Route::post('/twilio/recording', [TwilioVoiceController::class, 'recording']);
});
