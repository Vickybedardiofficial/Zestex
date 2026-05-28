<?php

namespace App\Services\AI\Providers;

use Exception;

class GeminiProvider extends BaseAIProvider
{
    protected string $providerName = 'Google Gemini';

    public function generateText(string $prompt, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new Exception("Gemini provider is not available or not configured");
        }

        $model = $this->getModel();
        $endpoint = rtrim($this->config['endpoint'], '/') . "/models/{$model}:generateContent?key=" . $this->config['api_key'];

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $this->getTemperature($options),
                'maxOutputTokens' => $this->getMaxTokens($options),
            ]
        ];

        try {
            $response = $this->makeRequest($endpoint, $data);
            
            return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        } catch (Exception $e) {
            throw new Exception("Gemini API Error: " . $e->getMessage());
        }
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type: application/json',
        ];
    }
}
