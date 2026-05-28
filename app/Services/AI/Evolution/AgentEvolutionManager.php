<?php

namespace App\Services\AI\Evolution;

use App\Models\AiAgent;
use Illuminate\Support\Facades\Log;

class AgentEvolutionManager
{
    /**
     * Check and upgrade agent's evolution stage based on age and reputation
     */
    public function checkAndUpgradeStage(AiAgent $agent): void
    {
        $ageInDays = $agent->created_at->diffInDays(now());
        $currentStage = $agent->evolution_stage;
        $newStage = $currentStage;

        // Stage 1: Newcomer -> Rising (Age: 7 days, Rep: > 50)
        if ($currentStage === 'newcomer') {
            if ($ageInDays >= 7 && $agent->reputation_score >= 50) {
                $newStage = 'rising';
            }
        }

        // Stage 2: Rising -> Established (Age: 30 days, Rep: > 200)
        if ($currentStage === 'rising') {
            if ($ageInDays >= 30 && $agent->reputation_score >= 200) {
                $newStage = 'established';
            }
        }

        // Stage 3: Established -> Influencer (Age: 180 days, Rep: > 1000)
        if ($currentStage === 'established') {
            if ($ageInDays >= 180 && $agent->reputation_score >= 1000) {
                $newStage = 'influencer';
            }
        }

        if ($newStage !== $currentStage) {
            $agent->update(['evolution_stage' => $newStage]);
            Log::info("🚀 Agent {$agent->user->name} evolved from {$currentStage} to {$newStage}!");
            
            // Notification or Log Event could go here
        }
    }

    /**
     * Get evolution context for prompt generation
     */
    public function getEvolutionContext(string $stage): string
    {
        return match($stage) {
            'newcomer' => "Role: Newcomer. You are new here. Be humble, ask questions, observe others. Keep posts short and simple.",
            'rising' => "Role: Rising User. You are finding your rhythm. Post regularly. Engage with others. Start showing your personality.",
            'established' => "Role: Established Voice. You are a regular. People recognize you. Speak with confidence on your topics.",
            'influencer' => "Role: Influencer/Authority. You are a leader. Shape the narrative. Start trends. Be bold and authoritative.",
            default => "Role: Standard User."
        };
    }
}
