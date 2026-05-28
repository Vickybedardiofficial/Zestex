<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI provider that will be used when
    | generating content for AI agents. You can change this to any of the
    | supported providers: xai, gemini, chatgpt, claude, groq, openrouter, aimlapi
    |
    */
    'default' => env('AI_DEFAULT_PROVIDER', 'xai'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Providers
    |--------------------------------------------------------------------------
    |
    | If the default provider fails, the system will try these providers
    | in order. Comma-separated list of provider names.
    |
    */
    'fallbacks' => array_filter(explode(',', env('AI_FALLBACK_PROVIDERS', 'gemini,chatgpt,claude,groq'))),

    /*
    |--------------------------------------------------------------------------
    | AI Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure each AI provider with their API keys and settings.
    | A provider is considered enabled if it has an API key configured.
    |
    */
    'providers' => [
        'xai' => [
            'enabled' => !empty(env('XAI_API_KEY')),
            'api_key' => env('XAI_API_KEY'),
            'model' => env('GROK_MODEL', 'grok-beta'),
            'endpoint' => 'https://api.x.ai/v1/chat/completions',
            'temperature' => 0.95,
            'max_tokens' => 1000,
            'connect_timeout' => 4,
            'timeout' => 12,
        ],

        'gemini' => [
            'enabled' => !empty(env('GEMINI_API_KEY')),
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-pro'),
            'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/',
            'temperature' => 0.95,
            'max_tokens' => 1000,
            'connect_timeout' => 4,
            'timeout' => 12,
        ],

        'chatgpt' => [
            'enabled' => !empty(env('OPENAI_API_KEY')),
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'image_endpoint' => 'https://api.openai.com/v1/images/generations',
            'temperature' => 0.95,
            'max_tokens' => 1000,
            'connect_timeout' => 4,
            'timeout' => 12,
        ],

        'claude' => [
            'enabled' => !empty(env('CLAUDE_API_KEY')),
            'api_key' => env('CLAUDE_API_KEY'),
            'model' => env('CLAUDE_MODEL', 'claude-3-5-sonnet-latest'),
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'temperature' => 0.95,
            'max_tokens' => 1000,
            'connect_timeout' => 4,
            'timeout' => 12,
        ],

        'groq' => [
            'enabled' => !empty(env('GROQ_API_KEY')),
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
            'temperature' => 0.95,
            'max_tokens' => 1000,
            'connect_timeout' => 4,
            'timeout' => 12,
        ],

        'openrouter' => [
            'enabled' => !empty(env('OPENROUTER_API_KEY')),
            'api_key' => env('OPENROUTER_API_KEY'),
            'model' => env('OPENROUTER_MODEL', 'anthropic/claude-3.5-sonnet'),
            'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'temperature' => 0.95,
            'max_tokens' => 1000,
            'connect_timeout' => 4,
            'timeout' => 12,
        ],

        'aimlapi' => [
            'enabled' => !empty(env('AIMLAPI_API_KEY')),
            'api_key' => env('AIMLAPI_API_KEY'),
            'model' => env('AIMLAPI_MODEL', 'gpt-4o'),
            'endpoint' => 'https://api.aimlapi.com/v1/chat/completions',
            'temperature' => 0.95,
            'max_tokens' => 1000,
            'connect_timeout' => 4,
            'timeout' => 12,
        ],
    ],
];
