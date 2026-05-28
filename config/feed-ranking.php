<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feed Ranking Defaults
    |--------------------------------------------------------------------------
    */
    'default_sort' => 'hot',
    'candidate_multiplier' => 12, // per_page * multiplier candidates before ranking
    'candidate_min' => 120,

    /*
    |--------------------------------------------------------------------------
    | Wilson Score
    |--------------------------------------------------------------------------
    */
    'wilson_z' => 1.96, // 95% confidence

    /*
    |--------------------------------------------------------------------------
    | Algorithm Tunings
    |--------------------------------------------------------------------------
    */
    'hot' => [
        'engagement_weight' => 0.25,
        'time_decay_per_hour' => 1.2,
    ],
    'top' => [
        'vote_weight' => 1.0,
        'engagement_weight' => 0.2,
    ],
    'rising' => [
        'engagement_multiplier' => 0.5,
        'min_age_hours' => 0.5,
    ],
    'controversial' => [
        'age_penalty_hours' => 24.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Context-Aware Boosts
    |--------------------------------------------------------------------------
    */
    'context' => [
        'country_match_boost' => 0.12,
        'city_match_boost' => 0.08,
        'area_match_boost' => 0.06,
        'cross_country_multiplier' => 0.90,
        'language_match_multiplier' => 1.08,
        'language_mismatch_multiplier' => 0.95,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature-Specific Multipliers
    |--------------------------------------------------------------------------
    */
    'feature_multipliers' => [
        'timeline' => [
            'hot' => 1.15,
            'new' => 1.00,
            'top' => 1.05,
            'rising' => 1.10,
            'controversial' => 1.00,
            'best' => 1.12,
        ],
        'timeline_public' => [
            'hot' => 1.05,
            'new' => 1.00,
            'top' => 1.05,
            'rising' => 1.10,
            'controversial' => 1.00,
            'best' => 1.08,
        ],
        'explore' => [
            'hot' => 1.00,
            'new' => 1.00,
            'top' => 1.12,
            'rising' => 1.15,
            'controversial' => 1.02,
            'best' => 1.10,
        ],
        'explore_public' => [
            'hot' => 1.00,
            'new' => 1.00,
            'top' => 1.10,
            'rising' => 1.12,
            'controversial' => 1.02,
            'best' => 1.08,
        ],
        'trending' => [
            'hot' => 1.20,
            'new' => 1.00,
            'top' => 1.15,
            'rising' => 1.25,
            'controversial' => 1.05,
            'best' => 1.15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Eligibility Thresholds (Actionable Rules)
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'engagement' => [
            'minimum_for_recommendation' => 20.0,
            'minimum_for_trending' => 45.0,
        ],
        'best' => [
            'minimum_for_recommendation' => 0.45,
            'minimum_for_trending' => 0.60,
        ],
        'hot' => [
            'minimum_for_recommendation' => 40.0,
            'minimum_for_trending' => 75.0,
        ],
        'rising' => [
            'minimum_for_recommendation' => 20.0,
            'minimum_for_viral_watch' => 60.0,
            'minimum_for_trending' => 100.0,
        ],
        'top' => [
            'minimum_for_top_feed' => 65.0,
        ],
        'controversial' => [
            'minimum_for_controversial_feed' => 12.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | New User / New Profile Boost
    |--------------------------------------------------------------------------
    */
    'new_creator' => [
        'enabled' => true,
        'max_account_age_hours' => 72,
        'quality_gate_best_score' => 0.35,
        'quality_gate_engagement' => 8.0,
        'boost_multiplier' => 1.12,
    ],
];
