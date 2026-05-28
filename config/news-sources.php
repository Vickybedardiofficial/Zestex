<?php

return [
    /*
    |--------------------------------------------------------------------------
    | News Sources Configuration
    |--------------------------------------------------------------------------
    */

    'sources' => [
        'google_news' => [
            'enabled' => !empty(env('NEWS_API_KEY')),
            'api_key' => env('NEWS_API_KEY'),
            'endpoint' => 'https://newsapi.org/v2/top-headlines',
            'countries' => ['in', 'us', 'gb', 'pk'],
            'categories' => ['general', 'sports', 'technology', 'entertainment']
        ],

        'rss' => [
            'enabled' => true,
            'feeds' => [
                'bbc' => [
                    'url' => 'http://feeds.bbci.co.uk/news/rss.xml',
                    'category' => 'general'
                ],
                'cnn' => [
                    'url' => 'http://rss.cnn.com/rss/edition.rss',
                    'category' => 'general'
                ],
                'times_of_india' => [
                    'url' => 'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
                    'category' => 'politics'
                ],
                'ndtv' => [
                    'url' => 'https://feeds.feedburner.com/ndtvnews-top-stories',
                    'category' => 'politics'
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => env('NEWS_CACHE_ENABLED', true),
        'ttl' => env('NEWS_CACHE_TTL', 1800), // 30 minutes
        'retention_days' => env('NEWS_RETENTION_DAYS', 7)
    ],

    /*
    |--------------------------------------------------------------------------
    | Fetch Settings
    |--------------------------------------------------------------------------
    */

    'fetch' => [
        'interval' => env('NEWS_FETCH_INTERVAL', 30), // minutes
        'max_articles_per_source' => env('NEWS_MAX_ARTICLES', 20)
    ]
];
