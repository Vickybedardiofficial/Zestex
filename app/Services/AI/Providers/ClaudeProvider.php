<?php

namespace App\Services\AI\Providers;

use Exception;

class ClaudeProvider extends BaseAIProvider
{
    protected string $providerName = 'Anthropic Claude';

    public function generateText(string $prompt, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new Exception("Claude provider is not available or not configured");
        }

        $endpoint = $this->config['endpoint'] ?? 'https://api.anthropic.com/v1/messages';

        $data = [
            'model' => $this->getModel(),
            'max_tokens' => $this->getMaxTokens($options),
            'temperature' => $this->getTemperature($options),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        try {
            $response = $this->makeRequest($endpoint, $data);
            return $response['content'][0]['text'] ?? '';
        } catch (Exception $e) {
            throw new Exception("Claude API Error: " . $e->getMessage());
        }
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type: application/json',
            'x-api-key: ' . $this->config['api_key'],
            'anthropic-version: 2023-06-01',
        ];
    }
}
