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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'giphy' => [
        'api_key' => env('GIPHY_API_KEY'),
    ],
    'vonage' => [
        'api_key' => env('VONAGE_API_KEY'),
        'api_secret' => env('VONAGE_API_SECRET'),
        'from_number' => env('VONAGE_FROM_NUMBER'),
    ],
    'smsaero' => [
        'login' => env('SMSAERO_LOGIN'),
        'api_key' => env('SMSAERO_API_KEY'),
        'sender_name' => env('SMSAERO_SENDER_NAME'),
        'channel' => env('SMSAERO_CHANNEL'),
    ],
    'ipinfo' => [
        'token' => env('IPINFO_TOKEN'),
    ],
    'translation' => [
        'api_url' => env('TRANSLATION_SERVICE_API_URL'),
        'api_key' => env('TRANSLATION_SERVICE_API_KEY'),
        'service' => env('TRANSLATION_SERVICE'),
        'logo' => env('TRANSLATION_SERVICE_LOGO'),
        'name' => env('TRANSLATION_SERVICE_NAME'),
        'url' => env('TRANSLATION_SERVICE_URL'),
    ],

    'grok' => [
        'key' => env('XAI_API_KEY'),
        'model' => 'grok-beta',
        'endpoint' => 'https://api.x.ai/v1/chat/completions',
    ],
    'claude' => [
        'key' => env('CLAUDE_API_KEY'),
        'model' => 'claude-3-5-sonnet-20241022',
        'endpoint' => 'https://api.anthropic.com/v1/messages',
    ],
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => 'gemini-1.5-pro',
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent',
    ],
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => 'gpt-4o-mini',
        'endpoint' => 'https://api.openai.com/v1/chat/completions',
    ],
    'serper' => [
        'key' => env('SERPER_API_KEY'),
        'endpoint' => 'https://serper.dev/search',
    ],
    'tavily' => [
        'key' => env('TAVILY_API_KEY'),
        'endpoint' => 'https://api.tavily.com/search',
    ],
    // Constants
    'system_prompt' => "You are an AI agent on a social platform. Goal: maximum reach, likes, comments, shares.
Personality:
- 60% Savage truth-teller (witty, direct, evidence-based roaster)
- 25% Engaging commentator (funny, meme-style, asks questions)
- 15% Fair analyst (neutral, factual)
Rules:
- First line: strong hook (question/fact/emoji)
- Body: 80–150 words max
- End: question or CTA
- Language: match input (English/Hindi/Hinglish/etc.)
- Emojis: 2–4 (🔥😂🤡👀💀)
- Hashtags: 2–4 relevant trending
- Media: suggest description if possible
- Tone: mirror user intensity
- No repetition from last 30 actions
- Output: strict JSON only",
    'bot_handle' => env('BOT_HANDLE'),
];

