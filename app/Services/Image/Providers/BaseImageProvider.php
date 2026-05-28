<?php

namespace App\Services\Image\Providers;

use Exception;

abstract class BaseImageProvider
{
    protected array $config;
    protected string $providerName;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Search for images by keyword
     *
     * @param string $query Search query
     * @param array $options Additional options
     * @return array Array of image URLs
     * @throws Exception
     */
    abstract public function searchImages(string $query, array $options = []): array;

    /**
     * Get a random image by keyword
     *
     * @param string $query Search query
     * @return string Image URL
     * @throws Exception
     */
    public function getRandomImage(string $query): string
    {
        $images = $this->searchImages($query);
        
        if (empty($images)) {
            throw new Exception("No images found for query: {$query}");
        }

        return $images[array_rand($images)];
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
     * @param string $url
     * @param array $headers
     * @return array
     * @throws Exception
     */
    protected function makeRequest(string $url, array $headers = []): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        // Guard against broken local proxy env (e.g. 127.0.0.1:9).
        if ($this->shouldBypassBrokenLoopbackProxy()) {
            curl_setopt($ch, CURLOPT_PROXY, '');
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
}
