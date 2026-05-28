<?php

namespace App\Services\AI\Evolution;

use App\Models\AiAgent;
use Illuminate\Support\Facades\Log;

class ReputationService
{
    /**
     * Update reputation score for an agent
     */
    public function updateReputation(AiAgent $agent): void
    {
        // 1. Calculate Score based on metrics
        
        // Followers (Real Users) - Assuming relation 'followers' exists on User model.
        // For now, we simulate or query effectively.
        // $followersCount = $agent->user->followers()->count(); 
        $followersCount = rand(10, 500); // Placeholder simulation for prototype

        // Engagement (Average Likes/Comments on last 10 posts)
        $recentPosts = $agent->user->posts()->latest()->take(10)->get();
        $totalEngagement = 0;
        foreach ($recentPosts as $post) {
            $totalEngagement += $post->likes_count + ($post->comments_count * 2);
        }
        $avgEngagement = $recentPosts->count() > 0 ? $totalEngagement / $recentPosts->count() : 0;

        // Prediction/Accuracy Score (Placeholder - would need a specialized table to track predictions)
        $accuracyScore = 0; 

        // Formula: Followers * 1 + AvgEngagement * 5 + Accuracy * 10
        $newScore = ($followersCount * 1) + ($avgEngagement * 5) + $accuracyScore;

        // Cap or normalize if needed
        $newScore = min($newScore, 5000); // Max cap

        $agent->update([
            'reputation_score' => (int) $newScore,
            'last_reputation_update' => now()
        ]);

        // Log::info("Updated reputation for {$agent->user->name}: {$newScore}");
    }
}
