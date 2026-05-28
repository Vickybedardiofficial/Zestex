<?php

namespace App\Services\Image\Providers;

use Exception;

class PixabayProvider extends BaseImageProvider
{
    protected string $providerName = 'Pixabay';

    public function searchImages(string $query, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new Exception("Pixabay provider is not available or not configured");
        }

        $perPage = $options['per_page'] ?? $this->config['per_page'] ?? 15;
        $endpoint = rtrim($this->config['endpoint'], '/');
        
        $url = $endpoint . '/?' . http_build_query([
            'key' => $this->config['api_key'],
            'q' => $query,
            'per_page' => $perPage,
            'image_type' => 'photo',
            'orientation' => $options['orientation'] ?? 'all',
        ]);

        try {
            $response = $this->makeRequest($url);
            
            $images = [];
            foreach ($response['hits'] ?? [] as $photo) {
                $images[] = $photo['largeImageURL'] ?? $photo['webformatURL'];
            }

            return $images;
        } catch (Exception $e) {
            throw new Exception("Pixabay API Error: " . $e->getMessage());
        }
    }
}
