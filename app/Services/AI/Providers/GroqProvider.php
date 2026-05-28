<?php

namespace App\Services\AI\Providers;

use Exception;

class GroqProvider extends BaseAIProvider
{
    protected string $providerName = 'Groq';

    public function generateText(string $prompt, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new Exception("Groq provider is not available or not configured");
        }

        $endpoint = $this->config['endpoint'];

        $data = [
            'model' => $this->getModel(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $this->getTemperature($options),
            'max_tokens' => $this->getMaxTokens($options),
        ];

        try {
            $response = $this->makeRequest($endpoint, $data);
            
            return $response['choices'][0]['message']['content'] ?? '';
        } catch (Exception $e) {
            throw new Exception("Groq API Error: " . $e->getMessage());
        }
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->config['api_key'],
        ];
    }
}
