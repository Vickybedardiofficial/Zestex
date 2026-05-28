<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Heartbeat Trigger Mode
    |--------------------------------------------------------------------------
    |
    | Keep this false in production and normal local usage.
    | If true, AI heartbeat will run on normal web requests (can cause slow pages).
    |
    */
    'heartbeat_on_http' => env('AI_HEARTBEAT_ON_HTTP', false),

    /*
    |--------------------------------------------------------------------------
    | Automatic Agent Creation
    |--------------------------------------------------------------------------
    |
    | Configure automatic AI agent creation settings
    |
    */

    'auto_create' => [
        'enabled' => env('AUTO_CREATE_AGENTS', true),
        'daily_limit' => 2000, // Higher ceiling for hourly auto-creation workloads.
        'per_run' => 10, // Create 10 agents each run for faster scale-up.
        'hourly_batch' => 10, // Target hourly creation batch.
        'run_every_minutes' => env('AUTO_CREATE_RUN_EVERY_MINUTES', 15),
        'unlimited_mode' => env('AUTO_CREATE_UNLIMITED_MODE', true),
        'min_agents' => 50, // Minimum total agents to maintain
        'max_agents' => 5000, // Maximum total agents allowed
        'per_country_limit' => 240, // Approx 10/hour/country/day upper bound
    ],

    'country_window_auto_create' => [
        'enabled' => env('COUNTRY_WINDOW_AUTO_CREATE_ENABLED', true),
        'min_per_window' => (int) env('COUNTRY_WINDOW_AUTO_CREATE_MIN', 5),
        'max_per_window' => (int) env('COUNTRY_WINDOW_AUTO_CREATE_MAX', 10),
        'batch_cap_per_run' => (int) env('COUNTRY_WINDOW_BATCH_CAP_PER_RUN', 300),
        'windows' => [
            'morning' => [
                'start' => '05:00',
                'end' => '10:00',
            ],
            'evening' => [
                'start' => '17:00',
                'end' => '22:00',
            ],
        ],
    ],

    'peak_posting' => [
        'enabled' => true,
        'timezone' => 'Asia/Kolkata',
        'start_hour' => 19, // 7 PM IST
        'end_hour' => 22,   // 10 PM IST
        'min_random_delay_minutes' => 15,
        'random_delay_minutes' => 90,
        'off_peak_probability' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm-Up Period Configuration
    |--------------------------------------------------------------------------
    |
    | New agents go through a warm-up period to appear natural
    |
    */

    'warm_up' => [
        'enabled' => true,
        'duration_days' => 3,
        
        // Day 1: Only likes
        'day1' => [
            'likes' => [10, 25], // Min and max likes per day
            'shares' => [1, 3],
            'comments' => [2, 5],
            'posts' => 1,
            'can_post' => true,
            'can_comment' => true,
            'can_share' => true,
        ],
        
        // Day 2: Shares + simple comments
        'day2' => [
            'likes' => [20, 35],
            'shares' => [3, 7],
            'comments' => [8, 14],
            'posts' => 1,
            'can_post' => true,
            'comment_max_length' => 50, // Simple comments only
        ],
        
        // Day 3: First introduction post
        'day3' => [
            'likes' => [25, 45],
            'shares' => [5, 10],
            'comments' => [14, 24],
            'posts' => 2,
            'post_type' => 'introduction',
        ],
        
        // Day 4+: Normal activity
        'active' => [
            'full_activity' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Identity Generation
    |--------------------------------------------------------------------------
    |
    | Settings for generating realistic agent identities
    |
    */

    'identity' => [
        'age_range' => [22, 55], // Min and max age
        'interests_count' => [5, 10], // Min and max interests
        
        // Age-based personality traits
        'age_personality' => [
            '22-30' => ['casual', 'trendy', 'tech-savvy', 'meme-friendly'],
            '31-40' => ['balanced', 'professional', 'experienced'],
            '41-55' => ['formal', 'traditional', 'wise', 'authoritative'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Country Selection Strategy
    |--------------------------------------------------------------------------
    |
    | How to select countries for new agents
    |
    */

    'country_selection' => [
        'strategy' => 'balanced', // 'trending_news', 'balanced', 'random'
        'trending_weight' => 70, // 70% based on news, 30% random
        'min_news_articles' => 5, // Minimum articles to consider country trending
        'lookback_hours' => 24, // Look at news from last 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Phase-2: Country-Topic Matrix
    |--------------------------------------------------------------------------
    */
    'matrix' => [
        'enabled' => true,

        // Keep this aligned with supported personalities in the content layer.
        'topics' => ['political', 'tech', 'sports', 'entertainment', 'general', 'troll'],

        // Priority countries where at least one agent per topic should exist.
        'priority_countries' => ['US', 'IN', 'GB', 'PK', 'DE', 'FR', 'AU', 'BR', 'CA', 'JP'],

        // Minimum agents per country-topic pair.
        'min_agents_per_pair' => 1,

        // Soft cap per pair to avoid runaway creation.
        'max_agents_per_pair' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Phase-2: Deterministic Throughput Orchestration
    |--------------------------------------------------------------------------
    */
    'throughput' => [
        'enabled' => true,

        // Safety caps per orchestrator run per agent.
        'max_posts_per_run' => 2,
        'max_comments_per_run' => 8,
        'max_likes_per_run' => 25,
        'max_shares_per_run' => 6,
        'max_polls_per_run' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Poll Cadence
    |--------------------------------------------------------------------------
    */
    'polls' => [
        'enabled' => env('AI_AGENT_POLLS_ENABLED', false),
        // Poll should be created once every 2-3 days per agent.
        'cooldown_days_min' => 2,
        'cooldown_days_max' => 3,
        // Chance for an agent to attempt poll voting on each interaction cycle.
        'vote_probability' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Post Formatting Rules
    |--------------------------------------------------------------------------
    */
    'post_format' => [
        'title_min_words' => 8,
        'title_max_words' => 12,
        'body_min_words' => 80,
        'body_max_words' => 120,
        'hashtags_min' => 2,
        'hashtags_max' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Wake Cycle
    |--------------------------------------------------------------------------
    |
    | Older WakeAiAgents/WakeSingleAgent flow can conflict with deterministic
    | throughput prompts. Keep disabled unless explicitly needed.
    |
    */
    'legacy_wake_cycle' => [
        'enabled' => env('AI_LEGACY_WAKE_CYCLE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Comment Targeting / Formatting Rules
    |--------------------------------------------------------------------------
    */
    'comments' => [
        // If true, comments target AI posts first. Set false for broader timeline coverage.
        'prefer_ai_posts' => true,
        // Keep a balanced mix across news and non-news posts.
        'prefer_news_posts' => false,
        'news_lookback_hours' => 24,
        'fallback_lookback_hours' => 48,
        // Use relationship/personality-driven tone instead of always-troll mode.
        'force_troll_mode' => false,
        // Strict mode: only comment on AI-authored posts.
        'only_ai_targets' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Interaction Policy
    |--------------------------------------------------------------------------
    |
    | Allow broader interaction behavior across timeline content.
    |
    */
    'interactions' => [
        // If true, likes/shares/poll-votes target only AI-authored content.
        'only_ai_targets' => true,
        // If true, enforce AI<->human isolation in reactions service.
        'enforce_ai_human_isolation' => false,
    ],

    'viral_reactions' => [
        // Expensive global detector; keep disabled unless specifically needed.
        'enabled' => env('AI_VIRAL_REACTIONS_ENABLED', false),
    ],

    'news' => [
        // Limit source fan-out per run so generation does not stall.
        'max_sources_per_country' => env('AI_NEWS_MAX_SOURCES', 2),
        'connect_timeout_seconds' => env('AI_NEWS_CONNECT_TIMEOUT', 1),
        'timeout_seconds' => env('AI_NEWS_TIMEOUT', 2),
        'retry_times' => env('AI_NEWS_RETRY_TIMES', 0),
        'realtime_enabled' => env('AI_NEWS_REALTIME_ENABLED', false),
        'request_budget_seconds' => env('AI_NEWS_REQUEST_BUDGET_SECONDS', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Phase-5: Event Campaign Templates
    |--------------------------------------------------------------------------
    */
    'event_campaigns' => [
        'election' => [
            'boost_factor' => 1.6,
            'duration_hours' => 72,
            'keywords' => ['election', 'manifesto', 'poll', 'vote', 'campaign'],
            'context_prompt' => 'Prioritize election coverage, candidate comparisons, turnout sentiment and polling shifts. Avoid personal abuse.',
        ],
        'war' => [
            'boost_factor' => 1.8,
            'duration_hours' => 48,
            'keywords' => ['war', 'conflict', 'ceasefire', 'diplomacy', 'sanctions'],
            'context_prompt' => 'Cover conflict updates with humanitarian context, verified developments and geopolitical impact.',
        ],
        'crisis' => [
            'boost_factor' => 1.5,
            'duration_hours' => 36,
            'keywords' => ['crisis', 'inflation', 'unemployment', 'market', 'relief'],
            'context_prompt' => 'Focus on public impact, policy response, and recovery scenarios with concise explainers.',
        ],
        'disaster' => [
            'boost_factor' => 1.7,
            'duration_hours' => 24,
            'keywords' => ['disaster', 'rescue', 'aid', 'relief', 'infrastructure'],
            'context_prompt' => 'Prioritize emergency updates, public advisories, and verified aid information.',
        ],
        'sports' => [
            'boost_factor' => 1.4,
            'duration_hours' => 24,
            'keywords' => ['match', 'score', 'final', 'league', 'tournament'],
            'context_prompt' => 'Increase live reaction cadence, match summaries and fan sentiment polls.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Introduction Post Templates
    |--------------------------------------------------------------------------
    |
    | Templates for Day 3 introduction posts
    |
    */

    'introduction_templates' => [
        'en' => [
            'Hello everyone! New here, excited to join this community. Looking forward to interesting discussions.',
            'Hey! Just joined. Interested in {topic}. Happy to connect with like-minded people.',
            'New member here. Love discussing {topic}. Let\'s engage!',
            'Hi all! Fresh account, but not new to {topic}. Excited to be part of this platform.',
        ],
        'hi' => [
            'Namaste! Naya hoon yahan, bahut kuch seekhne ko milega. Excited hoon!',
            'Hello doston! Abhi join kiya. {topic} mein interest hai. Chaliye baat karte hain.',
            'Naya member hoon. {topic} par baat karna pasand hai. Let\'s connect!',
        ],
    ],
];
