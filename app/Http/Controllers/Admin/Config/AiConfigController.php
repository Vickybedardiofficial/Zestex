<?php

namespace App\Http\Controllers\Admin\Config;

use App\Models\AdminSetting;
use App\Enums\AI\AIProvider;
use App\Enums\AI\ImageProvider;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Support\Views\Flash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiConfigController extends Controller
{
    public function index()
    {
        // Get all AI provider settings
        $aiProviders = AIProvider::cases();
        $imageProviders = ImageProvider::cases();

        // Get current settings from database
        $settings = AdminSetting::where('key', 'LIKE', 'ai_%')
            ->orWhere('key', 'LIKE', 'image_%')
            ->orWhere('key', 'LIKE', 'feed_ranking_%')
            ->orWhere('key', 'auto_agent_creation_enabled')
            ->orWhereIn('key', [
                'country_window_auto_create_enabled',
                'country_window_auto_create_morning_min',
                'country_window_auto_create_morning_max',
                'country_window_auto_create_evening_min',
                'country_window_auto_create_evening_max',
            ])
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->key => $item->value];
            });

        // Get API keys from database
        $apiKeys = $this->getStoredApiKeys();

        // Check which providers are enabled (have API keys)
        $settingsArray = $settings->toArray();
        $enabledAIProviders = $this->getEnabledAIProviders($apiKeys, $settingsArray);
        $enabledImageProviders = $this->getEnabledImageProviders($apiKeys, $settingsArray);

        return view('admin::config.ai.index', [
            'settings' => $settings,
            'aiProviders' => $aiProviders,
            'imageProviders' => $imageProviders,
            'enabledAIProviders' => $enabledAIProviders,
            'enabledImageProviders' => $enabledImageProviders,
            'apiKeys' => $apiKeys,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'auto_agent_creation_enabled' => 'nullable|string',
            'ai_engagement_enabled' => 'nullable|string',
            'country_window_auto_create_enabled' => 'nullable|string',
            'country_window_auto_create_morning_min' => 'nullable|integer|min:0|max:500',
            'country_window_auto_create_morning_max' => 'nullable|integer|min:0|max:500',
            'country_window_auto_create_evening_min' => 'nullable|integer|min:0|max:500',
            'country_window_auto_create_evening_max' => 'nullable|integer|min:0|max:500',
            'ai_default_provider' => 'nullable|string',
            'image_default_provider' => 'nullable|string',
            'ai_fallback_providers' => 'nullable|string',
            'image_fallback_providers' => 'nullable|string',
            'ai_provider_enabled_gemini' => 'nullable|string',
            'ai_provider_enabled_chatgpt' => 'nullable|string',
            'ai_provider_enabled_xai' => 'nullable|string',
            'ai_provider_enabled_claude' => 'nullable|string',
            'ai_provider_enabled_groq' => 'nullable|string',
            'ai_provider_enabled_openrouter' => 'nullable|string',
            'ai_provider_enabled_aimlapi' => 'nullable|string',
            'image_provider_enabled_pexels' => 'nullable|string',
            'image_provider_enabled_unsplash' => 'nullable|string',
            'image_provider_enabled_pixabay' => 'nullable|string',
            
            // AI Provider API Keys
            'gemini_api_key' => 'nullable|string',
            'openai_api_key' => 'nullable|string',
            'xai_api_key' => 'nullable|string',
            'claude_api_key' => 'nullable|string',
            'groq_api_key' => 'nullable|string',
            'openrouter_api_key' => 'nullable|string',
            'aimlapi_api_key' => 'nullable|string',
            
            // Image Provider API Keys
            'pexels_api_key' => 'nullable|string',
            'unsplash_api_key' => 'nullable|string',
            'pixabay_api_key' => 'nullable|string',

            // Feed Ranking Runtime Controls
            'feed_ranking_default_sort' => 'nullable|string|in:hot,new,top,rising,controversial,best',
            'feed_ranking_wilson_z' => 'nullable|numeric|min:1|max:3',
            'feed_ranking_thresholds_engagement_minimum_for_recommendation' => 'nullable|numeric|min:0|max:10000',
            'feed_ranking_thresholds_engagement_minimum_for_trending' => 'nullable|numeric|min:0|max:10000',
            'feed_ranking_thresholds_best_minimum_for_recommendation' => 'nullable|numeric|min:0|max:1',
            'feed_ranking_thresholds_best_minimum_for_trending' => 'nullable|numeric|min:0|max:1',
            'feed_ranking_thresholds_hot_minimum_for_recommendation' => 'nullable|numeric|min:0|max:10000',
            'feed_ranking_thresholds_hot_minimum_for_trending' => 'nullable|numeric|min:0|max:10000',
            'feed_ranking_thresholds_rising_minimum_for_recommendation' => 'nullable|numeric|min:0|max:10000',
            'feed_ranking_thresholds_rising_minimum_for_viral_watch' => 'nullable|numeric|min:0|max:10000',
            'feed_ranking_thresholds_rising_minimum_for_trending' => 'nullable|numeric|min:0|max:10000',
            'feed_ranking_context_country_match_boost' => 'nullable|numeric|min:0|max:2',
            'feed_ranking_context_city_match_boost' => 'nullable|numeric|min:0|max:2',
            'feed_ranking_context_area_match_boost' => 'nullable|numeric|min:0|max:2',
            'feed_ranking_context_cross_country_multiplier' => 'nullable|numeric|min:0|max:2',
            'feed_ranking_context_language_match_multiplier' => 'nullable|numeric|min:0|max:2',
            'feed_ranking_context_language_mismatch_multiplier' => 'nullable|numeric|min:0|max:2',
            'feed_ranking_new_creator_enabled' => 'nullable|string',
            'feed_ranking_new_creator_max_account_age_hours' => 'nullable|integer|min:1|max:720',
            'feed_ranking_new_creator_quality_gate_best_score' => 'nullable|numeric|min:0|max:1',
            'feed_ranking_new_creator_quality_gate_engagement' => 'nullable|numeric|min:0|max:10000',
            'feed_ranking_new_creator_boost_multiplier' => 'nullable|numeric|min:0|max:3',
        ]);

        $morningMin = (int) ($data['country_window_auto_create_morning_min'] ?? 0);
        $morningMax = (int) ($data['country_window_auto_create_morning_max'] ?? 0);
        $eveningMin = (int) ($data['country_window_auto_create_evening_min'] ?? 0);
        $eveningMax = (int) ($data['country_window_auto_create_evening_max'] ?? 0);

        if ($morningMax < $morningMin) {
            return redirect()->back()->withErrors([
                'country_window_auto_create_morning_max' => 'Morning max must be greater than or equal to morning min.',
            ])->withInput();
        }

        if ($eveningMax < $eveningMin) {
            return redirect()->back()->withErrors([
                'country_window_auto_create_evening_max' => 'Evening max must be greater than or equal to evening min.',
            ])->withInput();
        }

        if (empty($data['ai_fallback_providers'])) {
            $data['ai_fallback_providers'] = $this->deriveAiFallbackProviders($data);
        }

        // Save to database for admin panel display
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                AdminSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'type' => 'string']
                );
            }
        }
        Cache::forget('feed_ranking_runtime_overrides_v1');

        try {
            // Update .env file with API keys
            $this->updateEnvFile([
                'GEMINI_API_KEY' => $data['gemini_api_key'] ?? null,
                'OPENAI_API_KEY' => $data['openai_api_key'] ?? null,
                'XAI_API_KEY' => $data['xai_api_key'] ?? null,
                'CLAUDE_API_KEY' => $data['claude_api_key'] ?? null,
                'GROQ_API_KEY' => $data['groq_api_key'] ?? null,
                'OPENROUTER_API_KEY' => $data['openrouter_api_key'] ?? null,
                'AIMLAPI_API_KEY' => $data['aimlapi_api_key'] ?? null,
                'PEXELS_API_KEY' => $data['pexels_api_key'] ?? null,
                'UNSPLASH_ACCESS_KEY' => $data['unsplash_api_key'] ?? null,
                'PIXABAY_API_KEY' => $data['pixabay_api_key'] ?? null,
                'AI_DEFAULT_PROVIDER' => $data['ai_default_provider'] ?? null,
                'IMAGE_DEFAULT_PROVIDER' => $data['image_default_provider'] ?? null,
                'AI_FALLBACK_PROVIDERS' => $data['ai_fallback_providers'] ?? null,
                'IMAGE_FALLBACK_PROVIDERS' => $data['image_fallback_providers'] ?? null,
                'COUNTRY_WINDOW_AUTO_CREATE_ENABLED' => $data['country_window_auto_create_enabled'] ?? null,
                'COUNTRY_WINDOW_AUTO_CREATE_MORNING_MIN' => $data['country_window_auto_create_morning_min'] ?? null,
                'COUNTRY_WINDOW_AUTO_CREATE_MORNING_MAX' => $data['country_window_auto_create_morning_max'] ?? null,
                'COUNTRY_WINDOW_AUTO_CREATE_EVENING_MIN' => $data['country_window_auto_create_evening_min'] ?? null,
                'COUNTRY_WINDOW_AUTO_CREATE_EVENING_MAX' => $data['country_window_auto_create_evening_max'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to persist AI config env values', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('flashMessage', (new Flash(content: 'Settings saved in database, but .env update failed. Check file permissions.'))->get());
        }

        // Do not call `config:clear` from a web request (can cause request resets on local server).
        // New values are persisted and will be picked up on the next request/process.

        return redirect()->back()->with('flashMessage', (new Flash(content: 'AI & Image Provider settings updated successfully. Providers with API keys are now enabled.'))->get());
    }

    /**
     * Update .env file with new values
     */
    protected function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);
        if ($envContent === false) {
            throw new \RuntimeException('.env file is not readable.');
        }

        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Check if key exists in .env
            $pattern = "/^" . preg_quote($key, '/') . "=.*$/m";
            $normalizedValue = $this->normalizeEnvValue((string) $value);
            
            if (preg_match($pattern, $envContent)) {
                // Update existing key
                $envContent = preg_replace($pattern, "{$key}={$normalizedValue}", $envContent);
            } else {
                // Add new key at the end
                $envContent .= "\n{$key}={$normalizedValue}";
            }
        }

        if (file_put_contents($envPath, $envContent, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write .env file.');
        }
    }

    protected function normalizeEnvValue(string $value): string
    {
        // Preserve simple tokens as-is; quote values containing special characters/spaces.
        if (preg_match('/^[A-Za-z0-9_\-.,:@\/]+$/', $value)) {
            return $value;
        }

        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
        return '"' . $escaped . '"';
    }

    protected function deriveAiFallbackProviders(array $data): string
    {
        $default = (string) ($data['ai_default_provider'] ?? config('ai-providers.default', 'groq'));

        $enabled = [];
        if (!empty($data['openrouter_api_key']) && $this->isToggleEnabled($data['ai_provider_enabled_openrouter'] ?? null)) {
            $enabled[] = 'openrouter';
        }
        if (!empty($data['aimlapi_api_key']) && $this->isToggleEnabled($data['ai_provider_enabled_aimlapi'] ?? null)) {
            $enabled[] = 'aimlapi';
        }
        if (!empty($data['groq_api_key']) && $this->isToggleEnabled($data['ai_provider_enabled_groq'] ?? null)) {
            $enabled[] = 'groq';
        }
        if (!empty($data['openai_api_key']) && $this->isToggleEnabled($data['ai_provider_enabled_chatgpt'] ?? null)) {
            $enabled[] = 'chatgpt';
        }
        if (!empty($data['claude_api_key']) && $this->isToggleEnabled($data['ai_provider_enabled_claude'] ?? null)) {
            $enabled[] = 'claude';
        }
        if (!empty($data['gemini_api_key']) && $this->isToggleEnabled($data['ai_provider_enabled_gemini'] ?? null)) {
            $enabled[] = 'gemini';
        }
        if (!empty($data['xai_api_key']) && $this->isToggleEnabled($data['ai_provider_enabled_xai'] ?? null)) {
            $enabled[] = 'xai';
        }

        $fallbacks = array_values(array_filter(array_unique($enabled), fn (string $p) => $p !== $default));
        return implode(',', $fallbacks);
    }

    /**
     * Get stored API keys from database
     */
    protected function getStoredApiKeys(): array
    {
        $keys = AdminSetting::whereIn('key', [
            'gemini_api_key',
            'openai_api_key',
            'xai_api_key',
            'claude_api_key',
            'groq_api_key',
            'openrouter_api_key',
            'aimlapi_api_key',
            'pexels_api_key',
            'unsplash_api_key',
            'pixabay_api_key',
        ])->get()->mapWithKeys(function ($item) {
            return [$item->key => $item->value];
        });

        return $keys->toArray();
    }

    /**
     * Get enabled AI providers based on stored API keys
     */
    protected function getEnabledAIProviders(array $apiKeys, array $settings): array
    {
        $enabled = [];
        
        if (!empty($apiKeys['gemini_api_key']) && $this->isToggleEnabled($settings['ai_provider_enabled_gemini'] ?? null)) $enabled[] = 'gemini';
        if (!empty($apiKeys['openai_api_key']) && $this->isToggleEnabled($settings['ai_provider_enabled_chatgpt'] ?? null)) $enabled[] = 'chatgpt';
        if (!empty($apiKeys['claude_api_key']) && $this->isToggleEnabled($settings['ai_provider_enabled_claude'] ?? null)) $enabled[] = 'claude';
        if (!empty($apiKeys['xai_api_key']) && $this->isToggleEnabled($settings['ai_provider_enabled_xai'] ?? null)) $enabled[] = 'xai';
        if (!empty($apiKeys['groq_api_key']) && $this->isToggleEnabled($settings['ai_provider_enabled_groq'] ?? null)) $enabled[] = 'groq';
        if (!empty($apiKeys['openrouter_api_key']) && $this->isToggleEnabled($settings['ai_provider_enabled_openrouter'] ?? null)) $enabled[] = 'openrouter';
        if (!empty($apiKeys['aimlapi_api_key']) && $this->isToggleEnabled($settings['ai_provider_enabled_aimlapi'] ?? null)) $enabled[] = 'aimlapi';

        return $enabled;
    }

    /**
     * Get enabled image providers based on stored API keys
     */
    protected function getEnabledImageProviders(array $apiKeys, array $settings): array
    {
        $enabled = [];
        
        if (!empty($apiKeys['pexels_api_key']) && $this->isToggleEnabled($settings['image_provider_enabled_pexels'] ?? null)) $enabled[] = 'pexels';
        if (!empty($apiKeys['unsplash_api_key']) && $this->isToggleEnabled($settings['image_provider_enabled_unsplash'] ?? null)) $enabled[] = 'unsplash';
        if (!empty($apiKeys['pixabay_api_key']) && $this->isToggleEnabled($settings['image_provider_enabled_pixabay'] ?? null)) $enabled[] = 'pixabay';
        
        // AI Generated is auto-enabled if any AI provider is enabled
        if (!empty($this->getEnabledAIProviders($apiKeys, $settings))) {
            $enabled[] = 'ai_generated';
        }

        return $enabled;
    }

    protected function isToggleEnabled(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }
}

