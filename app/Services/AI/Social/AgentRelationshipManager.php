<?php

namespace App\Services\AI\Social;

use App\Models\AiAgent;

class AgentRelationshipManager
{
    /**
     * Determine the relationship between two agents
     * Returns: 'ally', 'rival', 'mentor', 'neutral'
     */
    public function getRelationship(AiAgent $agentA, AiAgent $agentB): string
    {
        // 1. Check for Mentor Relationship (Age Gap)
        $ageGap = $agentA->created_at->diffInMonths($agentB->created_at);
        
        // If A is older by 6+ months
        if ($ageGap > 6 && $agentA->created_at < $agentB->created_at) {
            return 'mentor'; 
        }
        // If B is older by 6+ months
        if ($ageGap > 6 && $agentB->created_at < $agentA->created_at) {
            return 'mentee'; // From A's perspective, B is a mentor, so A is mentee (handled effectively as neutral/respectful)
        }

        // 2. Check Political Leaning (Primary Factor for Rivalry/Alliance)
        $politicsA = $agentA->political_leaning;
        $politicsB = $agentB->political_leaning;

        if ($politicsA && $politicsB) {
            if ($politicsA === $politicsB) {
                return 'ally';
            }

            // Define Opposite Pairs
            $opposites = [
                'left' => 'right',
                'right' => 'left',
                'liberal' => 'conservative',
                'conservative' => 'liberal',
                'socialist' => 'capitalist',
                'capitalist' => 'socialist',
            ];

            if (($opposites[$politicsA] ?? '') === $politicsB) {
                return 'rival';
            }
        }

        // 3. check Topics/Interests (Secondary Factor for Alliance)
        $commonTopics = array_intersect($agentA->topics ?? [], $agentB->topics ?? []);
        if (count($commonTopics) >= 2) {
            return 'ally'; // Shared interests imply alliance
        }

        return 'neutral';
    }

    /**
     * Get interaction style based on relationship
     */
    public function getInteractionStyle(string $relationship): string
    {
        return match($relationship) {
            'ally' => 'supportive',
            'rival' => 'argumentative',
            'mentor' => 'encouraging',
            'mentee' => 'respectful',
            default => 'neutral'
        };
    }
}
