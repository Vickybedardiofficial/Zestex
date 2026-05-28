<?php

namespace App\Services\Image;

use App\Enums\AI\ImageProvider as ImageProviderEnum;
use App\Models\AdminSetting;
use App\Services\Image\Providers\BaseImageProvider;
use App\Services\Image\Providers\PexelsProvider;
use App\Services\Image\Providers\UnsplashProvider;
use App\Services\Image\Providers\PixabayProvider;
use App\Services\AI\AIProviderManager;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageProviderManager
{
    protected ?BaseImageProvider $currentProvider = null;
    protected array $providers = [];
    protected AIProviderManager $aiManager;
    protected array $runtimeConfig = [];

    public function __construct()
    {
        $this->runtimeConfig = $this->buildRuntimeConfig();
        $this->initializeProviders();
        $this->aiManager = new AIProviderManager();
    }

    /**
     * Initialize all available providers
     */
    protected function initializeProviders(): void
    {
        $config = $this->runtimeConfig['providers'] ?? config('image-providers.providers');

        foreach ([ImageProviderEnum::PEXELS, ImageProviderEnum::UNSPLASH, ImageProviderEnum::PIXABAY] as $providerEnum) {
            $providerConfig = $config[$providerEnum->value] ?? [];
            
            if (empty($providerConfig) || !($providerConfig['enabled'] ?? false)) {
                continue;
            }

            $provider = $this->createProvider($providerEnum, $providerConfig);
            
            if ($provider && $provider->isAvailable()) {
                $this->providers[$providerEnum->value] = $provider;
            }
        }
    }

    /**
     * Create a provider instance
     */
    protected function createProvider(ImageProviderEnum $providerEnum, array $config): ?BaseImageProvider
    {
        return match ($providerEnum) {
            ImageProviderEnum::PEXELS => new PexelsProvider($config),
            ImageProviderEnum::UNSPLASH => new UnsplashProvider($config),
            ImageProviderEnum::PIXABAY => new PixabayProvider($config),
            default => null,
        };
    }

    /**
     * Get a specific provider or the default one
     */
    public function getProvider(?string $providerName = null): BaseImageProvider
    {
        if ($providerName && isset($this->providers[$providerName])) {
            return $this->providers[$providerName];
        }

        // Try default provider
        $defaultProvider = $this->runtimeConfig['default'] ?? config('image-providers.default');
        if (isset($this->providers[$defaultProvider])) {
            return $this->providers[$defaultProvider];
        }

        // Try fallback providers
        $fallbacks = $this->runtimeConfig['fallbacks'] ?? config('image-providers.fallbacks', []);
        foreach ($fallbacks as $fallback) {
            if (isset($this->providers[$fallback])) {
                return $this->providers[$fallback];
            }
        }

        // Return first available provider
        if (!empty($this->providers)) {
            return reset($this->providers);
        }

        throw new Exception("No image providers are available. Please configure at least one provider.");
    }

    /**
     * Get random image by keyword
     */
    public function getRandomImage(string $query, ?string $providerName = null, array $options = []): string
    {
        // Check if AI generated is requested
        if ($providerName === 'ai_generated') {
            return $this->generateAIImage($query, $options);
        }

        $provider = $this->getProvider($providerName);
        $selectedProviderKey = $providerName ?: ($this->runtimeConfig['default'] ?? config('image-providers.default'));

        try {
            return $provider->getRandomImage($query);
        } catch (Exception $e) {
            // Always attempt fallbacks if primary fails.
            $fallbacks = $this->runtimeConfig['fallbacks'] ?? config('image-providers.fallbacks', []);
            $candidates = array_values(array_unique(array_merge($fallbacks, array_keys($this->providers))));

            foreach ($candidates as $fallback) {
                if ($fallback === $selectedProviderKey || !isset($this->providers[$fallback])) {
                    continue;
                }

                try {
                    return $this->providers[$fallback]->getRandomImage($query);
                } catch (Exception $fallbackError) {
                    continue;
                }
            }

            throw $e;
        }
    }

    /**
     * Generate image using AI
     */
    protected function generateAIImage(string $prompt, array $options = []): string
    {
        try {
            return $this->aiManager->generateImage($prompt, null, $options);
        } catch (Exception $e) {
            throw new Exception("AI Image Generation Error: " . $e->getMessage());
        }
    }

    /**
     * Download and store image
     */
    public function downloadAndStore(string $imageUrl, string $directory = 'ai-agents/avatars'): string
    {
        try {
            $imageContent = file_get_contents($imageUrl);
            
            if ($imageContent === false) {
                throw new Exception("Failed to download image from URL");
            }

            $filename = uniqid('avatar_') . '.jpg';
            $path = $directory . '/' . $filename;

            Storage::disk('public')->put($path, $imageContent);

            return Storage::disk('public')->url($path);
        } catch (Exception $e) {
            throw new Exception("Image Download Error: " . $e->getMessage());
        }
    }

    /**
     * Get all available providers
     */
    public function getAvailableProviders(): array
    {
        $providers = array_keys($this->providers);
        
        // Add AI generated if any AI provider is available
        if (!empty($this->aiManager->getAvailableProviders())) {
            $providers[] = 'ai_generated';
        }

        return $providers;
    }

    protected function buildRuntimeConfig(): array
    {
        $config = [
            'default' => config('image-providers.default'),
            'fallbacks' => config('image-providers.fallbacks', []),
            'providers' => config('image-providers.providers', []),
        ];

        try {
            $settings = AdminSetting::whereIn('key', [
                'image_default_provider',
                'image_fallback_providers',
                'image_provider_enabled_pexels',
                'image_provider_enabled_unsplash',
                'image_provider_enabled_pixabay',
                'pexels_api_key',
                'unsplash_api_key',
                'pixabay_api_key',
            ])->pluck('value', 'key')->toArray();

            if (!empty($settings['image_default_provider'])) {
                $config['default'] = $settings['image_default_provider'];
            }

            if (!empty($settings['image_fallback_providers'])) {
                $config['fallbacks'] = array_values(array_filter(array_map('trim', explode(',', $settings['image_fallback_providers']))));
            }

            $keyMap = [
                'pexels' => $settings['pexels_api_key'] ?? null,
                'unsplash' => $settings['unsplash_api_key'] ?? null,
                'pixabay' => $settings['pixabay_api_key'] ?? null,
            ];
            $toggleMap = [
                'pexels' => $this->parseToggleSetting($settings['image_provider_enabled_pexels'] ?? null),
                'unsplash' => $this->parseToggleSetting($settings['image_provider_enabled_unsplash'] ?? null),
                'pixabay' => $this->parseToggleSetting($settings['image_provider_enabled_pixabay'] ?? null),
            ];

            foreach ($keyMap as $provider => $apiKey) {
                if (!isset($config['providers'][$provider])) {
                    continue;
                }

                if (($toggleMap[$provider] ?? true) === false) {
                    $config['providers'][$provider]['enabled'] = false;
                    continue;
                }

                if (!empty($apiKey)) {
                    $config['providers'][$provider]['api_key'] = $apiKey;
                    $config['providers'][$provider]['enabled'] = true;
                } elseif (empty($config['providers'][$provider]['api_key'])) {
                    $config['providers'][$provider]['enabled'] = false;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ImageProviderManager runtime config fallback to file config', [
                'error' => $e->getMessage(),
            ]);
        }

        return $config;
    }

    protected function parseToggleSetting(?string $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
            return false;
        }

        return null;
    }
}
