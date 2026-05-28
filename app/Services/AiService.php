<?php

namespace App\Services;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    public const PROVIDER_GROK = 'grok';
    public const PROVIDER_GEMINI = 'gemini';
    public const PROVIDER_CHATGPT = 'chatgpt';

    protected $provider;
    protected $apiKeys = [];

    public function __construct()
    {
        $this->loadSettings();
    }

    protected function loadSettings()
    {
        // Load active provider
        $this->provider = AdminSetting::where('key', 'ai_active_provider')->value('value') ?? self::PROVIDER_GROK;

        // Load keys
        $settings = AdminSetting::whereIn('key', [
            'ai_grok_api_key',
            'ai_grok_model',
            'ai_gemini_api_key',
            'ai_openai_api_key'
        ])->pluck('value', 'key');

        $this->apiKeys = [
            self::PROVIDER_GROK => $settings['ai_grok_api_key'] ?? config('services.grok.api_key'), // Fallback to config
            self::PROVIDER_GEMINI => $settings['ai_gemini_api_key'] ?? null,
            self::PROVIDER_CHATGPT => $settings['ai_openai_api_key'] ?? null,
        ];
        
        $this->models = [
            self::PROVIDER_GROK => $settings['ai_grok_model'] ?? config('services.grok.model', 'grok-beta'),
             // Add others if needed
        ];
    }

    public function getReply(string $prompt, string $systemPrompt): ?string
    {
        switch ($this->provider) {
            case self::PROVIDER_GEMINI:
                return $this->callGemini($prompt, $systemPrompt);
            case self::PROVIDER_CHATGPT:
                return $this->callChatGPT($prompt, $systemPrompt);
            case self::PROVIDER_GROK:
            default:
                return $this->callGrok($prompt, $systemPrompt);
        }
    }

    protected function callGrok(string $prompt, string $systemPrompt): ?string
    {
        $apiKey = $this->apiKeys[self::PROVIDER_GROK];
        $model = $this->models[self::PROVIDER_GROK];

        if (! $apiKey) {
            Log::error('AiService: Grok API Key missing.');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.x.ai/v1/chat/completions', [
                'model'       => $model,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens'  => 200,
                'temperature' => 0.8,
            ]);

            if ($response->failed()) {
                Log::error('AiService: Grok API failed', $response->json());
                return null;
            }

            return trim($response->json()['choices'][0]['message']['content'] ?? '');
        } catch (\Exception $e) {
            Log::error('AiService: Grok Exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function callGemini(string $prompt, string $systemPrompt): ?string
    {
        $apiKey = $this->apiKeys[self::PROVIDER_GEMINI];
        
        if (! $apiKey) {
            Log::error('AiService: Gemini API Key missing.');
            return null;
        }

        // Gemini API Implementation (Google Generative AI)
        // URL: https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=API_KEY
        
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt . "\n\n" . $prompt] // System prompt simulation for Gemini
                        ]
                    ]
                ]
            ]);

             if ($response->failed()) {
                Log::error('AiService: Gemini API failed', $response->json());
                return null;
            }
            
            return trim($response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '');

        } catch (\Exception $e) {
             Log::error('AiService: Gemini Exception: ' . $e->getMessage());
             return null;
        }
    }

    protected function callChatGPT(string $prompt, string $systemPrompt): ?string
    {
        $apiKey = $this->apiKeys[self::PROVIDER_CHATGPT];
        
        if (! $apiKey) {
             Log::error('AiService: OpenAI API Key missing.');
             return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => 'gpt-4o-mini', // Defaulting to 4o-mini for cost/speed
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens'  => 200,
            ]);

             if ($response->failed()) {
                Log::error('AiService: OpenAI API failed', $response->json());
                return null;
            }

            return trim($response->json()['choices'][0]['message']['content'] ?? '');

        } catch (\Exception $e) {
             Log::error('AiService: OpenAI Exception: ' . $e->getMessage());
             return null;
        }
    }
}
