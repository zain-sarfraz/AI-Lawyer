<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_PHONE_NUMBER'),
        'validate_signature' => env('TWILIO_VALIDATE_SIGNATURE', true),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'realtime_model' => env('OPENAI_REALTIME_MODEL', 'gpt-4o-realtime-preview'),
        'summary_model' => env('OPENAI_SUMMARY_MODEL', 'gpt-4o-mini'),
    ],

    'voice_gateway' => [
        'secret' => env('VOICE_GATEWAY_SECRET'),
        'public_ws_url' => env('VOICE_GATEWAY_PUBLIC_WS_URL'),
        'url' => env('VOICE_GATEWAY_URL', 'http://127.0.0.1:3100'),
    ],

    'finetuner' => [
        'api_key' => env('FINETUNER_API_KEY'),
        'workspace_id' => env('FINETUNER_WORKSPACE_ID'),
        'base_url' => env('FINETUNER_API_BASE', 'https://api.synthflow.ai/v2'),
    ],

];
