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

    'vin_check' => [
        'base_url' => env('VIN_CHECK_BASE_URL'),
        'api_key' => env('VIN_CHECK_API_KEY'),
        'webhook_secret' => env('VIN_CHECK_WEBHOOK_SECRET'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_API_KEY'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    'aec' => [
        'url' => env('AEC_API_URL'),
        'token_ttl' => env('AEC_API_TOKEN_CACHE_TTL', 3600),
    ],

    'copart' => [
        'url' => env('COPART_API_URL'),
    ],

    'bitrix' => [
        'webhook' => env('WEBHOOK_B24'),
    ],

    'dealer_calc' => [
        'sso_secret' => env('DEALER_CALC_SSO_SECRET'),
        'callback' => env('DEALER_CALC_SSO_CALLBACK_URL'),
    ],

    'kafka' => [
        'brokers' => env('KAFKA_BROKERS'),
    ],

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST'),
        'key' => env('MEILISEARCH_KEY'),
    ],

];
