<?php

namespace App\Services\Image\Providers;

use Exception;

class UnsplashProvider extends BaseImageProvider
{
    protected string $providerName = 'Unsplash';

    public function searchImages(string $query, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new Exception("Unsplash provider is not available or not configured");
        }

        $perPage = $options['per_page'] ?? $this->config['per_page'] ?? 15;
        $endpoint = rtrim($this->config['endpoint'], '/');
        
        $url = $endpoint . '?' . http_build_query([
            'query' => $query,
            'per_page' => $perPage,
            'orientation' => $options['orientation'] ?? 'squarish',
        ]);

        $headers = [
            'Authorization: Client-ID ' . $this->config['api_key'],
        ];

        try {
            $response = $this->makeRequest($url, $headers);
            
            $images = [];
            foreach ($response['results'] ?? [] as $photo) {
                $images[] = $photo['urls']['regular'] ?? $photo['urls']['full'];
            }

            return $images;
        } catch (Exception $e) {
            throw new Exception("Unsplash API Error: " . $e->getMessage());
        }
    }
}
