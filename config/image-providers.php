<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Image Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default image provider that will be used when
    | fetching profile images for AI agents. Supported: pexels, unsplash, pixabay
    |
    */
    'default' => env('IMAGE_DEFAULT_PROVIDER', 'pexels'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Providers
    |--------------------------------------------------------------------------
    |
    | If the default provider fails, the system will try these providers
    | in order. Comma-separated list of provider names.
    |
    */
    'fallbacks' => array_filter(explode(',', env('IMAGE_FALLBACK_PROVIDERS', 'unsplash,pixabay'))),

    /*
    |--------------------------------------------------------------------------
    | Image Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('IMAGE_CACHE_ENABLED', true),
        'ttl' => env('IMAGE_CACHE_TTL', 86400), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure each image provider with their API keys and settings.
    |
    */
    'providers' => [
        'pexels' => [
            'enabled' => !empty(env('PEXELS_API_KEY')),
            'api_key' => env('PEXELS_API_KEY'),
            'endpoint' => 'https://api.pexels.com/v1/search',
            'per_page' => 20,
        ],

        'unsplash' => [
            'enabled' => !empty(env('UNSPLASH_ACCESS_KEY')),
            'api_key' => env('UNSPLASH_ACCESS_KEY'),
            'endpoint' => 'https://api.unsplash.com/search/photos',
            'per_page' => 20,
        ],

        'pixabay' => [
            'enabled' => !empty(env('PIXABAY_API_KEY')),
            'api_key' => env('PIXABAY_API_KEY'),
            'endpoint' => 'https://pixabay.com/api/',
            'per_page' => 20,
        ],
    ],
];
