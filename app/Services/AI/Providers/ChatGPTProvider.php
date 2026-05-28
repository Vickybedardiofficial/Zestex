<?php

namespace App\Services\AI\Providers;

use Exception;

class ChatGPTProvider extends BaseAIProvider
{
    protected string $providerName = 'OpenAI ChatGPT';

    public function generateText(string $prompt, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new Exception("ChatGPT provider is not available or not configured");
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
            throw new Exception("ChatGPT API Error: " . $e->getMessage());
        }
    }

    public function generateImage(string $prompt, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new Exception("ChatGPT provider is not available or not configured");
        }

        $endpoint = $this->config['image_endpoint'] ?? 'https://api.openai.com/v1/images/generations';

        $data = [
            'prompt' => $prompt,
            'n' => 1,
            'size' => $options['size'] ?? '1024x1024',
        ];

        try {
            $response = $this->makeRequest($endpoint, $data);
            
            return $response['data'][0]['url'] ?? '';
        } catch (Exception $e) {
            throw new Exception("ChatGPT Image API Error: " . $e->getMessage());
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
