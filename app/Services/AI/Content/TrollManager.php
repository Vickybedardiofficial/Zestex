<?php

namespace App\Services\AI\Content;

use App\Models\AiAgent;
use App\Services\AI\Prompts\AgentPromptTemplate;
use Illuminate\Support\Facades\Log;

class TrollManager
{
    /**
     * Check if a news item triggers any Troll Mechanism
     * Returns: 'contradiction', 'promise_broken', 'hypocrisy', 'irony', 'timing', or null
     */
    public function detectTrigger($newsItem): ?string
    {
        // In a real LLM system, we would feed the news content to an analyzer.
        // Here, we simulate detection based on keywords/tags for the prototype.
        
        $text = strtolower($newsItem['title'] . ' ' . ($newsItem['summary'] ?? ''));

        // 1. Contradiction Detector (You said X, now Y)
        if (str_contains($text, 'u-turn') || str_contains($text, 'backtrack') || str_contains($text, 'reverses stance')) {
            return 'contradiction';
        }

        // 2. Promise Tracker (Deadline passed)
        if (str_contains($text, 'missed deadline') || str_contains($text, 'delayed') || str_contains($text, 'promise unfulfilled')) {
            return 'promise_broken';
        }

        // 3. Hypocrisy Spotter (Do what I say, not what I do)
        if (str_contains($text, 'private jet') && str_contains($text, 'climate')) {
            return 'hypocrisy'; // Classic example
        }
        if (str_contains($text, 'exposed') || str_contains($text, 'caught')) {
            return 'hypocrisy';
        }

        // 4. Irony Highlighter
        if (str_contains($text, 'ironically') || str_contains($text, 'unexpectedly')) {
            return 'irony';
        }

        // 5. Timing Attack (Bad timing)
        if (str_contains($text, 'while') && str_contains($text, 'amidst') && str_contains($text, 'crisis')) {
            return 'timing';
        }

        return null;
    }

    /**
     * Generate a specialized Troll Prompt based on the Mechanism
     */
    public function generateTrollPrompt(AiAgent $agent, $newsItem, string $mechanism): string
    {
        $systemProfile = (new AgentPromptTemplate())->build($agent);
        $basePrompt = $systemProfile . "\n\n";
        $basePrompt .= "You are an accountability analyst named {$agent->user->name}. Your goal is to highlight contradictions with evidence and a calm, respectful tone. ";
        $basePrompt .= "Focus only on public statements or actions described in the news. Avoid speculation or personal attacks. ";
        $basePrompt .= "News: \"{$newsItem['title']}\". ";

        $prompts = [
            'contradiction' => "Mechanism: Contradiction Detector. Task: Show a before vs after contrast and ask for clarification. Keep it factual and neutral.",
            'promise_broken' => "Mechanism: Promise Tracker. Task: Remind about the deadline and ask for an update. Keep it neutral and specific.",
            'hypocrisy' => "Mechanism: Consistency Check. Task: Highlight inconsistency between words and actions with evidence, without mockery.",
            'irony' => "Mechanism: Irony Highlighter. Task: Point out irony without mockery. Keep it respectful.",
            'timing' => "Mechanism: Timing Check. Task: Note timing concerns and ask for context."
        ];

        $instructions = $prompts[$mechanism] ?? "Task: Provide a concise evidence-based observation.";

        // SAFETY FILTER INJECTION
        $safetyChecks = "\n\n[SAFETY GUIDELINES - STRICT]:\n";
        $safetyChecks .= "1. NO personal attacks, insults, or demeaning labels.\n";
        $safetyChecks .= "2. Target only PUBLIC actions and statements.\n";
        $safetyChecks .= "3. NO hate speech, slurs, threats, or harassment.\n";
        $safetyChecks .= "4. Keep it evidence-based, neutral, and respectful.\n";
        $safetyChecks .= "5. Do not mirror aggressive language.\n";
        $safetyChecks .= "6. If evidence is weak or unclear, ask a clarifying question instead of accusing.\n";

        $formatRules = "\n\n[FORMAT]: Start with 🤖 on its own line. Then 1-3 short lines. If citing evidence, add one line starting with 📎.";

        return $basePrompt . $instructions . $safetyChecks . $formatRules;
    }

    /**
     * Validate generated content for safety (Post-Generation Filter)
     */
    public function isSafe(string $content): bool
    {
        $forbidden = [
            'sexist', 'racist', 'kill', 'die', 'dumb', 'ugly', 'fat', 'stupid', 'idiot', 'moron',
            'loser', 'scum', 'trash', 'clown', 'fraud', 'liar', 'pathetic', 'worthless',
            'threat', 'attack', 'harm', 'rape', 'murder', 'suicide',
            'family', 'wife', 'husband', 'children', 'kids', 'son', 'daughter'
        ];

        $lower = strtolower($content);
        foreach ($forbidden as $word) {
            if (str_contains($lower, $word)) {
                Log::warning("Troll content blocked due to safety filter: $word");
                return false;
            }
        }
        return true;
    }
}
