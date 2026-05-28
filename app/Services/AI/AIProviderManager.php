<?php

namespace App\Services\AI;

use App\Enums\AI\AIProvider as AIProviderEnum;
use App\Models\AdminSetting;
use App\Services\AI\Providers\BaseAIProvider;
use App\Services\AI\Providers\XAIProvider;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\ChatGPTProvider;
use App\Services\AI\Providers\ClaudeProvider;
use App\Services\AI\Providers\GroqProvider;
use App\Services\AI\Providers\OpenRouterProvider;
use App\Services\AI\Providers\AIMLAPIProvider;
use Exception;
use Illuminate\Support\Facades\Log;

class AIProviderManager
{
    protected ?BaseAIProvider $currentProvider = null;
    protected array $providers = [];
    protected array $runtimeConfig = [];

    public function __construct()
    {
        $this->runtimeConfig = $this->buildRuntimeConfig();
        $this->initializeProviders();
    }

    /**
     * Initialize all available providers
     */
    protected function initializeProviders(): void
    {
        $config = $this->runtimeConfig['providers'] ?? config('ai-providers.providers', []);

        foreach (AIProviderEnum::cases() as $providerEnum) {
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
    protected function createProvider(AIProviderEnum $providerEnum, array $config): ?BaseAIProvider
    {
        return match ($providerEnum) {
            AIProviderEnum::XAI => new XAIProvider($config),
            AIProviderEnum::GEMINI => new GeminiProvider($config),
            AIProviderEnum::CHATGPT => new ChatGPTProvider($config),
            AIProviderEnum::CLAUDE => new ClaudeProvider($config),
            AIProviderEnum::GROQ => new GroqProvider($config),
            AIProviderEnum::OPENROUTER => new OpenRouterProvider($config),
            AIProviderEnum::AIMLAPI => new AIMLAPIProvider($config),
        };
    }

    /**
     * Get a specific provider or the default one
     */
    public function getProvider(?string $providerName = null): BaseAIProvider
    {
        if ($providerName && isset($this->providers[$providerName])) {
            return $this->providers[$providerName];
        }

        // Try default provider
        $defaultProvider = $this->runtimeConfig['default'] ?? config('ai-providers.default');
        if (isset($this->providers[$defaultProvider])) {
            return $this->providers[$defaultProvider];
        }

        // Try fallback providers
        $fallbacks = $this->runtimeConfig['fallbacks'] ?? config('ai-providers.fallbacks', []);
        foreach ($fallbacks as $fallback) {
            if (isset($this->providers[$fallback])) {
                return $this->providers[$fallback];
            }
        }

        // Return first available provider
        if (!empty($this->providers)) {
            return reset($this->providers);
        }

        throw new Exception("No AI providers are available. Please configure at least one provider.");
    }

    /**
     * Generate text using specified or default provider
     */
    public function generateText(string $prompt, ?string $providerName = null, array $options = []): string
    {
        $provider = $this->getProvider($providerName);
        $selectedProviderKey = $providerName ?: ($this->runtimeConfig['default'] ?? config('ai-providers.default'));

        try {
            return $provider->generateText($prompt, $options);
        } catch (Exception $e) {
            // Always attempt fallbacks, even when providerName is null.
            $fallbacks = $this->runtimeConfig['fallbacks'] ?? config('ai-providers.fallbacks', []);
            $candidates = array_values(array_unique(array_merge($fallbacks, array_keys($this->providers))));

            foreach ($candidates as $fallback) {
                if ($fallback === $selectedProviderKey || !isset($this->providers[$fallback])) {
                    continue;
                }

                try {
                    return $this->providers[$fallback]->generateText($prompt, $options);
                } catch (Exception $fallbackError) {
                    continue;
                }
            }

            throw $e;
        }
    }

    /**
     * Generate image using specified or default provider
     */
    public function generateImage(string $prompt, ?string $providerName = null, array $options = []): string
    {
        $provider = $this->getProvider($providerName);
        return $provider->generateImage($prompt, $options);
    }

    /**
     * Get all available providers
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Check if a specific provider is available
     */
    public function isProviderAvailable(string $providerName): bool
    {
        return isset($this->providers[$providerName]);
    }

    protected function buildRuntimeConfig(): array
    {
        $config = [
            'default' => config('ai-providers.default'),
            'fallbacks' => config('ai-providers.fallbacks', []),
            'providers' => config('ai-providers.providers', []),
        ];

        try {
            $settings = AdminSetting::whereIn('key', [
                'ai_default_provider',
                'ai_active_provider',
                'ai_fallback_providers',
                'ai_provider_enabled_gemini',
                'ai_provider_enabled_chatgpt',
                'ai_provider_enabled_xai',
                'ai_provider_enabled_claude',
                'ai_provider_enabled_groq',
                'ai_provider_enabled_openrouter',
                'ai_provider_enabled_aimlapi',
                'xai_api_key',
                'gemini_api_key',
                'openai_api_key',
                'groq_api_key',
                'claude_api_key',
                'openrouter_api_key',
                'aimlapi_api_key',
                // Legacy keys supported by older admin API
                'ai_grok_api_key',
                'ai_openai_api_key',
                'ai_gemini_api_key',
                'ai_grok_model',
            ])->pluck('value', 'key')->toArray();

            $config['default'] = $settings['ai_default_provider']
                ?? $settings['ai_active_provider']
                ?? $config['default'];

            if (!empty($settings['ai_fallback_providers'])) {
                $config['fallbacks'] = array_values(array_filter(array_map('trim', explode(',', $settings['ai_fallback_providers']))));
            }

            $keyMap = [
                'xai' => $settings['xai_api_key'] ?? $settings['ai_grok_api_key'] ?? null,
                'gemini' => $settings['gemini_api_key'] ?? $settings['ai_gemini_api_key'] ?? null,
                'chatgpt' => $settings['openai_api_key'] ?? $settings['ai_openai_api_key'] ?? null,
                'claude' => $settings['claude_api_key'] ?? null,
                'groq' => $settings['groq_api_key'] ?? null,
                'openrouter' => $settings['openrouter_api_key'] ?? null,
                'aimlapi' => $settings['aimlapi_api_key'] ?? null,
            ];
            $toggleMap = [
                'xai' => $this->parseToggleSetting($settings['ai_provider_enabled_xai'] ?? null),
                'gemini' => $this->parseToggleSetting($settings['ai_provider_enabled_gemini'] ?? null),
                'chatgpt' => $this->parseToggleSetting($settings['ai_provider_enabled_chatgpt'] ?? null),
                'claude' => $this->parseToggleSetting($settings['ai_provider_enabled_claude'] ?? null),
                'groq' => $this->parseToggleSetting($settings['ai_provider_enabled_groq'] ?? null),
                'openrouter' => $this->parseToggleSetting($settings['ai_provider_enabled_openrouter'] ?? null),
                'aimlapi' => $this->parseToggleSetting($settings['ai_provider_enabled_aimlapi'] ?? null),
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

            if (!empty($settings['ai_grok_model']) && isset($config['providers']['xai'])) {
                $config['providers']['xai']['model'] = $settings['ai_grok_model'];
            }

            // If no explicit fallback is configured, auto-build from enabled providers.
            if (empty($config['fallbacks'])) {
                $default = (string) ($config['default'] ?? '');
                $priority = ['openrouter', 'aimlapi', 'claude', 'groq', 'chatgpt', 'gemini', 'xai'];
                $config['fallbacks'] = array_values(array_filter($priority, function (string $provider) use ($config, $default) {
                    if ($provider === $default) {
                        return false;
                    }
                    return !empty($config['providers'][$provider]['enabled']);
                }));
            }
        } catch (\Throwable $e) {
            Log::warning('AIProviderManager runtime config fallback to file config', [
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
