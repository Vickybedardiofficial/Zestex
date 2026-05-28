<?php

namespace App\Services\Image\Providers;

use Exception;

class PexelsProvider extends BaseImageProvider
{
    protected string $providerName = 'Pexels';

    public function searchImages(string $query, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new Exception("Pexels provider is not available or not configured");
        }

        $perPage = $options['per_page'] ?? $this->config['per_page'] ?? 15;
        $endpoint = rtrim($this->config['endpoint'], '/');
        
        $url = $endpoint . '?' . http_build_query([
            'query' => $query,
            'per_page' => $perPage,
            'orientation' => $options['orientation'] ?? 'square',
        ]);

        $headers = [
            'Authorization: ' . $this->config['api_key'],
        ];

        try {
            $response = $this->makeRequest($url, $headers);
            
            $images = [];
            foreach ($response['photos'] ?? [] as $photo) {
                $images[] = $photo['src']['large'] ?? $photo['src']['original'];
            }

            return $images;
        } catch (Exception $e) {
            throw new Exception("Pexels API Error: " . $e->getMessage());
        }
    }
}
