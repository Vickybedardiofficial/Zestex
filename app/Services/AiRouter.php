<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRouter
{
    public function generate(string $prompt, array $context = []): array
    {
        $apis = collect(['grok', 'claude', 'gemini', 'openai'])
            ->filter(function (string $api) {
                return !empty(config("services.{$api}.key"));
            })
            ->values()
            ->all();

        if (empty($apis)) {
            Log::warning('AiRouter: no provider keys configured in config/services.php');
            return ['action' => 'none'];
        }

        foreach ($apis as $api) {
            try {
                $response = $this->call($api, $prompt, $context);
                if (!empty($response)) {
                     // Check if response is a direct string or nested structure depending on provider
                     // The requirement is to return a JSON array with action decision
                     // We expect the LLM to return a JSON string as requested in system prompt
                     
                     // Helper to extract content based on provider structure if not uniform, 
                     // but here we standardized call() return to be the raw API response array.
                     // Actually, let's make call() return the content string directly or throw.
                     
                     return $this->parseResponse($api, $response);
                }
            } catch (\Exception $e) {
                Log::warning("{$api} API failed: " . $e->getMessage());
            }
        }

        return ['action' => 'none'];
    }

    protected function call(string $api, string $prompt, array $context): array
    {
        $config = config("services.{$api}");
        
        if (empty($config['key'])) {
             throw new \Exception("API key for {$api} is missing.");
        }

        $systemPrompt = config('services.system_prompt');
        $fullPrompt = $prompt . "\nContext: " . json_encode($context);

        // Normalize request per provider
        // Grok / OpenAI
        if ($api === 'grok' || $api === 'openai') {
            return Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['key'],
                'Content-Type' => 'application/json',
            ])->post($config['endpoint'], [
                'model' => $config['model'],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $fullPrompt],
                ],
                'max_tokens' => 300,
                'temperature' => 0.95,
            ])->throw()->json();
        }
        
        // Claude
        if ($api === 'claude') {
             return Http::withHeaders([
                'x-api-key' => $config['key'],
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post($config['endpoint'], [
                'model' => $config['model'],
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $fullPrompt],
                ],
                'max_tokens' => 300,
                'temperature' => 0.95,
            ])->throw()->json();
        }
        
        // Gemini
        if ($api === 'gemini') {
            $url = $config['endpoint'] . '?key=' . $config['key'];
            return Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                             ['text' => $systemPrompt . "\n\n" . $fullPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 300,
                    'temperature' => 0.95,
                ]
            ])->throw()->json();
        }

        throw new \Exception("Unknown API provider: {$api}");
    }
    
    protected function parseResponse(string $api, array $response): array
    {
        $content = '';
        
        if ($api === 'grok' || $api === 'openai') {
            $content = $response['choices'][0]['message']['content'] ?? '';
        } elseif ($api === 'claude') {
            $content = $response['content'][0]['text'] ?? '';
        } elseif ($api === 'gemini') {
            $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }
        
        // Clean markdown code blocks if present
        $content = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
        
        $decoded = json_decode($content, true);
        if (!is_array($decoded) && str_starts_with($content, '{') && str_ends_with($content, '}')) {
            $normalized = preg_replace('/,\s*}/', '}', $content);
            $decoded = json_decode((string) $normalized, true);
        }
        return is_array($decoded) ? $decoded : ['action' => 'none'];
    }
}
