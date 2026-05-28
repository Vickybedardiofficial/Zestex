<?php

namespace App\Services\AI\Social;

use App\Models\AiAgent;
use App\Models\Post;
use Carbon\Carbon;

class ChainReactionManager
{
    /**
     * Determine if a post should trigger a chain reaction phase
     * Returns: 'initial', 'heating_up', 'peak', 'cooldown', or null
     */
    public function getReactionPhase(Post $post): ?string
    {
        $ageInMinutes = $post->created_at->diffInMinutes(now());

        if ($ageInMinutes < 5) {
            return 'initial'; // Time for immediate rivals/allies to react
        }

        if ($ageInMinutes >= 5 && $ageInMinutes < 15) {
            return 'heating_up'; // 2-3 more agents join
        }

        if ($ageInMinutes >= 15 && $ageInMinutes < 30) {
            return 'peak'; // Full debate
        }

        if ($ageInMinutes >= 30 && $ageInMinutes < 60) {
            return 'cooldown'; // Summary or final thoughts
        }

        return null; // Too old for active chain reaction
    }

    /**
     * Get the target number of reactions for this phase
     */
    public function getTargetReactionCount(string $phase): int
    {
        return match($phase) {
            'initial' => 1,      // Just 1 instigator
            'heating_up' => 3,   // 2-3 people arguing
            'peak' => 6,         // Full thread
            'cooldown' => 8,     // Saturated
            default => 0
        };
    }
}
