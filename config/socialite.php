<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Socialite Providers
    |--------------------------------------------------------------------------
    |
    | Configuration for all supported Socialite providers. Each provider
    | can be enabled/disabled and configured with their specific settings.
    |
    */

    'providers' => [
        
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT_URI'),
        ],

    ],

];
