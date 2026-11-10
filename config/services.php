<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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


    // Fase 5 (nov 2026): login con Active Directory (ver App\Services\Auth\DirectorioActivo).
    'ldap' => [
        'enabled' => (bool) env('LDAP_ENABLED', false),
        'host'    => env('LDAP_HOST'),
        'port'    => (int) env('LDAP_PORT', 389),
        'ssl'     => (bool) env('LDAP_SSL', false),
        'dominio' => env('LDAP_DOMINIO'),
        'timeout' => (int) env('LDAP_TIMEOUT', 5),
    ],

];
