<?php

namespace App\Services\AI\Content;

use App\Models\AiAgent;
use App\Services\AI\AIProviderManager;
use App\Services\AI\Prompts\AgentPromptTemplate;
use Illuminate\Support\Facades\Log;

class PollGenerator
{
    protected AIProviderManager $aiManager;

    public function __construct()
    {
        $this->aiManager = new AIProviderManager();
    }

    /**
     * Generate a poll based on context and type
     */
    public function generatePoll(AiAgent $agent, string $type = 'random'): array
    {
        if ($type === 'random') {
            $types = ['yes_no', 'opinion', 'prediction', 'comparison', 'blame_game', 'ranking', 'hypothetical', 'troll'];
            $type = $types[array_rand($types)];
            
            // Bias towards opinion and yes_no for simplicity
            if ($agent->personality_type === 'political') {
                $type = array_rand(array_flip(['opinion', 'blame_game', 'prediction', 'yes_no']));
            } elseif ($agent->personality_type === 'entertainment') {
                $type = array_rand(array_flip(['ranking', 'hypothetical', 'troll', 'comparison']));
            }
        }

        $prompt = $this->buildPrompt($agent, $type);
        
        try {
            $response = $this->aiManager->generateText($prompt, $agent->ai_provider, [
                'temperature' => 0.9,
                'max_tokens' => 150
            ]); // Returns JSON string if provider supports it, otherwise text we need to parse.
            
            // For stability, we assume the AI returns a JSON string as requested in prompt
            // Clean markdown code blocks if any
            $response = str_replace(['```json', '```'], '', $response);
            $pollData = json_decode(trim($response), true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($pollData['question']) || !isset($pollData['choices'])) {
                throw new \Exception("Invalid JSON from AI: " . $response);
            }

            return [
                'type' => $type,
                'question' => $pollData['question'],
                'choices' => $this->formatChoices($pollData['choices']),
                'duration' => 24, // hours
            ];

        } catch (\Exception $e) {
            Log::error("Poll Generation Failed: " . $e->getMessage());
            return $this->getFallbackPoll($agent);
        }
    }

    protected function buildPrompt(AiAgent $agent, string $type): string
    {
        $systemProfile = (new AgentPromptTemplate())->build($agent);
        $base = $systemProfile . "\n\n";
        $base .= "You are a {$agent->personality_type} AI agent from {$agent->country}. Create a {$type} poll for a social media post. Output ONLY valid JSON format: {\"question\": \"...\", \"choices\": [\"Option 1\", \"Option 2\", ...]}. ";

        $instructions = [
            'yes_no' => "Ask a direct Yes/No question about a current event or strong opinion. Choices: Yes, No, Neutral.",
            
            'opinion' => "Ask for public opinion on a trending topic. 4 distinct choices representing different views.",
            
            'prediction' => "Ask users to predict a future event (Election, Match, Economy). 4 choices (Improving, Worsening, Same, etc.).",
            
            'comparison' => "Compare two policies, people, or things. Choices: A, B, Both, Neither.",
            
            'blame_game' => "Ask who is responsible for a current problem. Choices: Govt, Opposition, Public, External Factors.",
            
            'ranking' => "Ask what should be the priority. Choices: 4 different issues (e.g., Jobs, Inflation, Safety).",
            
            'hypothetical' => "Ask 'What would you choose if...?'. 4 creative scenarios.",
            
            'troll' => "Ask a sarcastic or funny question about a controversial topic. Choices should be funny/sarcastic options."
        ];

        return $base . ($instructions[$type] ?? $instructions['opinion']) . " In {$agent->language}. Keep question under 100 chars, choices under 30 chars.";
    }

    protected function formatChoices(array $choices): array
    {
        $choices = array_values(array_filter(array_map(function ($choice) {
            return trim((string) $choice);
        }, $choices)));

        // Ensure exactly 4 options for consistent UI behavior.
        $fallback = ['Option A', 'Option B', 'Option C', 'Option D'];
        while (count($choices) < 4) {
            $choices[] = $fallback[count($choices)];
        }

        return array_slice($choices, 0, 4);
    }

    protected function getFallbackPoll(AiAgent $agent): array
    {
        return [
            'type' => 'opinion',
            'question' => "What matters most to you right now?",
            'choices' => ["Stability", "Freedom", "Economy", "Health"],
            'duration' => 24
        ];
    }
}
