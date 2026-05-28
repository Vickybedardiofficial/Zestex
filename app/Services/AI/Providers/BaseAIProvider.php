<?php

namespace App\Services\AI\Providers;

use Exception;

abstract class BaseAIProvider
{
    protected array $config;
    protected string $providerName;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Generate text content using the AI provider
     *
     * @param string $prompt The prompt to generate content from
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @return string Generated text
     * @throws Exception
     */
    abstract public function generateText(string $prompt, array $options = []): string;

    /**
     * Generate an image using the AI provider (if supported)
     *
     * @param string $prompt The image description
     * @param array $options Additional options
     * @return string Image URL or path
     * @throws Exception
     */
    public function generateImage(string $prompt, array $options = []): string
    {
        throw new Exception("Image generation not supported by {$this->providerName}");
    }

    /**
     * Check if the provider is available and configured
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty($this->config['api_key']) && ($this->config['enabled'] ?? false);
    }

    /**
     * Get the provider name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->providerName;
    }

    /**
     * Make an HTTP request to the provider API
     *
     * @param string $endpoint
     * @param array $data
     * @param string $method
     * @return array
     * @throws Exception
     */
    protected function makeRequest(string $endpoint, array $data, string $method = 'POST'): array
    {
        $ch = curl_init();

        $headers = $this->getHeaders();
        $connectTimeout = (int) ($this->config['connect_timeout'] ?? 4);
        $timeout = (int) ($this->config['timeout'] ?? 12);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => max(1, $connectTimeout),
            CURLOPT_TIMEOUT => max(2, $timeout),
        ]);

        // Guard against misconfigured local proxy env (e.g. 127.0.0.1:9) that breaks all outbound AI calls.
        if ($this->shouldBypassBrokenLoopbackProxy()) {
            curl_setopt($ch, CURLOPT_PROXY, '');
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP Error {$httpCode}: {$response}");
        }

        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Decode Error: " . json_last_error_msg());
        }

        return $decoded;
    }

    protected function shouldBypassBrokenLoopbackProxy(): bool
    {
        $proxy = getenv('HTTPS_PROXY') ?: getenv('HTTP_PROXY') ?: getenv('ALL_PROXY');
        if (empty($proxy)) {
            return false;
        }

        $parts = parse_url($proxy);
        if (empty($parts) || empty($parts['host'])) {
            return false;
        }

        $host = strtolower((string) $parts['host']);
        $port = (int) ($parts['port'] ?? 0);

        return in_array($host, ['127.0.0.1', 'localhost', '::1'], true) && in_array($port, [9, 0], true);
    }

    /**
     * Get HTTP headers for API requests
     *
     * @return array
     */
    abstract protected function getHeaders(): array;

    /**
     * Get the model name from config
     *
     * @return string
     */
    protected function getModel(): string
    {
        return $this->config['model'] ?? 'default';
    }

    /**
     * Get temperature setting
     *
     * @param array $options
     * @return float
     */
    protected function getTemperature(array $options = []): float
    {
        return $options['temperature'] ?? $this->config['temperature'] ?? 0.7;
    }

    /**
     * Get max tokens setting
     *
     * @param array $options
     * @return int
     */
    protected function getMaxTokens(array $options = []): int
    {
        return $options['max_tokens'] ?? $this->config['max_tokens'] ?? 1000;
    }
}
