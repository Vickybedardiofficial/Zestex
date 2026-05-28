<?php

namespace App\Services\AI;

use App\Enums\User\UserStatus;
use App\Enums\User\UserType;
use App\Models\AiAgent;
use App\Models\User;
use App\Services\AI\ProfileGenerator\ProfileMediaGenerator;
use App\Services\AI\Profile\IdentityGenerator;
use App\Services\AI\News\CountryNewsAggregator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AutoAgentCreator
{
    protected IdentityGenerator $identityGenerator;
    protected CountryNewsAggregator $newsAggregator;
    protected ProfileMediaGenerator $profileMediaGenerator;

    public function __construct()
    {
        $this->identityGenerator = new IdentityGenerator();
        $this->newsAggregator = new CountryNewsAggregator();
        $this->profileMediaGenerator = new ProfileMediaGenerator();
    }

    /**
     * Auto-create agents based on trending news
     */
    public function createAgents(int $count = null): array
    {
        // Part 1: Admin Switch Check
        $isEnabled = DB::table('admin_settings')->where('key', 'auto_agent_creation_enabled')->value('value');
        $isEnabled = $this->parseBooleanSetting($isEnabled);

        // If explicitly disabled in admin settings, always stop.
        if ($isEnabled === false) {
             Log::info('Auto agent creation is disabled by Admin.');
             return [];
        }

        // Fallback to legacy config if database setting is missing
        if ($isEnabled === null && !config('agent-creation.auto_create.enabled')) {
            return [];
        }

        $count = $count ?? config('agent-creation.auto_create.per_run', 10);
        $windowModeEnabled = $this->resolveBooleanSetting(
            'country_window_auto_create_enabled',
            (bool) config('agent-creation.country_window_auto_create.enabled', false)
        );
        $unlimitedMode = (bool) config('agent-creation.auto_create.unlimited_mode', false);

        if ($windowModeEnabled) {
            return $this->createAgentsByCountryWindow($count);
        }

        $created = [];

        if (!$unlimitedMode) {
            // Check if we've reached DAILY limit
            $dailyLimit = config('agent-creation.auto_create.daily_limit', 10);
            $createdToday = AiAgent::where('auto_created', true)
                ->whereDate('created_at', now()->toDateString())
                ->count();

            if ($createdToday >= $dailyLimit) {
                Log::info('Daily auto-creation limit reached.', ['today' => $createdToday, 'limit' => $dailyLimit]);
                return [];
            }

            // Adjust count if we are close to limit
            $remainingToday = $dailyLimit - $createdToday;
            $count = min($count, $remainingToday);

            // Check if we've reached max agents
            $totalAgents = AiAgent::count();
            $maxAgents = config('agent-creation.auto_create.max_agents', 500);

            if ($totalAgents >= $maxAgents) {
                Log::info('Max agents limit reached', ['total' => $totalAgents, 'max' => $maxAgents]);
                return [];
            }
        }

        // Determine countries to create agents for
        $countries = $this->selectCountries($count);

        $personalityCycle = $this->buildPersonalityCycle();
        $perCountryLimit = (int) config('agent-creation.auto_create.per_country_limit', 10);

        foreach ($countries as $index => $country) {
            try {
                $countryCode = strtoupper((string) $country);
                if (!$unlimitedMode && $perCountryLimit > 0) {
                    $countryCreatedToday = AiAgent::query()
                        ->where('auto_created', true)
                        ->where('country', $countryCode)
                        ->whereDate('created_at', now()->toDateString())
                        ->count();

                    if ($countryCreatedToday >= $perCountryLimit) {
                        continue;
                    }
                }

                $personality = $personalityCycle[$index % count($personalityCycle)];
                $agent = $this->createAgentForCountryTopic($countryCode, $personality);
                $created[] = $agent;
                
                Log::info('Auto-created agent', [
                    'agent_id' => $agent->id,
                    'country' => $country,
                    'name' => $agent->user->name,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to auto-create agent', [
                    'country' => $country,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $created;
    }

    protected function createAgentsByCountryWindow(int $count): array
    {
        $created = [];
        $allCountries = array_keys(config('countries.countries', []));
        $personalityCycle = $this->buildPersonalityCycle();
        $batchCap = max(1, (int) config('agent-creation.country_window_auto_create.batch_cap_per_run', 300));
        $maxToCreate = max(1, min($batchCap, $count > 0 ? $count * count($allCountries) : $batchCap));
        $createdSoFar = 0;

        foreach ($allCountries as $index => $country) {
            if ($createdSoFar >= $maxToCreate) {
                break;
            }

            $window = $this->getActiveWindowForCountry($country);
            if (!$window) {
                continue;
            }

            $target = $this->windowTargetForCountry($country, $window['name'], $window['local_date']);
            $createdInWindow = AiAgent::query()
                ->where('auto_created', true)
                ->where('country', strtoupper($country))
                ->whereBetween('created_at', [$window['start_utc'], $window['end_utc']])
                ->count();

            $need = max(0, $target - $createdInWindow);
            if ($need <= 0) {
                continue;
            }

            $remainingBatch = $maxToCreate - $createdSoFar;
            $toCreate = min($need, $remainingBatch);

            for ($i = 0; $i < $toCreate; $i++) {
                try {
                    $personality = $personalityCycle[($index + $i) % count($personalityCycle)];
                    $agent = $this->createAgentForCountryTopic(strtoupper($country), $personality);
                    $created[] = $agent;
                    $createdSoFar++;
                } catch (\Throwable $e) {
                    Log::error('Country-window auto-create failed', [
                        'country' => $country,
                        'window' => $window['name'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $created;
    }

    protected function getActiveWindowForCountry(string $country): ?array
    {
        $timezone = (string) config("countries.countries.{$country}.timezone", 'UTC');
        $localNow = now($timezone);
        $windows = (array) config('agent-creation.country_window_auto_create.windows', []);

        foreach ($windows as $name => $def) {
            $startAt = (string) ($def['start'] ?? '00:00');
            $endAt = (string) ($def['end'] ?? '00:00');

            [$startHour, $startMinute] = array_pad(array_map('intval', explode(':', $startAt)), 2, 0);
            [$endHour, $endMinute] = array_pad(array_map('intval', explode(':', $endAt)), 2, 0);

            $startLocal = Carbon::create($localNow->year, $localNow->month, $localNow->day, $startHour, $startMinute, 0, $timezone);
            $endLocal = Carbon::create($localNow->year, $localNow->month, $localNow->day, $endHour, $endMinute, 0, $timezone);

            if ($endLocal->lessThanOrEqualTo($startLocal)) {
                $endLocal->addDay();
            }

            if ($localNow->between($startLocal, $endLocal)) {
                return [
                    'name' => (string) $name,
                    'local_date' => $localNow->toDateString(),
                    'start_utc' => $startLocal->copy()->timezone('UTC'),
                    'end_utc' => $endLocal->copy()->timezone('UTC'),
                ];
            }
        }

        return null;
    }

    protected function windowTargetForCountry(string $country, string $window, string $date): int
    {
        $defaultMin = (int) config('agent-creation.country_window_auto_create.min_per_window', 5);
        $defaultMax = (int) config('agent-creation.country_window_auto_create.max_per_window', 10);

        if ($window === 'morning') {
            $min = $this->resolveIntSetting('country_window_auto_create_morning_min', $defaultMin);
            $max = $this->resolveIntSetting('country_window_auto_create_morning_max', $defaultMax);
        } elseif ($window === 'evening') {
            $min = $this->resolveIntSetting('country_window_auto_create_evening_min', $defaultMin);
            $max = $this->resolveIntSetting('country_window_auto_create_evening_max', $defaultMax);
        } else {
            $min = $defaultMin;
            $max = $defaultMax;
        }

        if ($max < $min) {
            $max = $min;
        }

        $seed = strtoupper($country) . '|' . $window . '|' . $date;
        $hash = (int) sprintf('%u', crc32($seed));

        return $min + ($hash % ($max - $min + 1));
    }

    protected function resolveIntSetting(string $key, int $fallback): int
    {
        try {
            $value = DB::table('admin_settings')->where('key', $key)->value('value');
            if ($value === null || $value === '') {
                return $fallback;
            }

            return (int) $value;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    protected function resolveBooleanSetting(string $key, bool $fallback): bool
    {
        try {
            $value = DB::table('admin_settings')->where('key', $key)->value('value');
            if ($value === null) {
                return $fallback;
            }

            $parsed = $this->parseBooleanSetting($value);
            return $parsed === null ? $fallback : $parsed;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    /**
     * Select countries based on trending news
     */
    protected function selectCountries(int $count): array
    {
        $strategy = config('agent-creation.country_selection.strategy', 'trending_news');
        
        if ($strategy === 'trending_news') {
            try {
                $countries = $this->selectTrendingCountries($count);
                if (empty($countries)) {
                     Log::warning('Trending news returned no countries, falling back to random.');
                     return $this->selectRandomCountries($count);
                }
                return $countries;
            } catch (\Exception $e) {
                Log::warning('Trending news strategy failed, falling back to random: ' . $e->getMessage());
                return $this->selectRandomCountries($count);
            }
        }

        if ($strategy === 'balanced') {
            return $this->selectBalancedCountries($count);
        }

        return $this->selectRandomCountries($count);
    }

    /**
     * Select countries based on trending news
     */
    protected function selectTrendingCountries(int $count): array
    {
        $allCountries = array_keys(config('countries.countries', []));
        $trendingWeight = config('agent-creation.country_selection.trending_weight', 70);
        
        // Get news activity per country
        $newsActivity = [];
        foreach ($allCountries as $country) {
            $newsCount = $this->newsAggregator->fetchCountryNews($country, 10);
            $newsActivity[$country] = count($newsCount);
        }

        // Sort by news activity
        arsort($newsActivity);
        
        $selected = [];
        $trendingCount = (int) ceil($count * $trendingWeight / 100);
        $randomCount = $count - $trendingCount;

        // Select trending countries
        $trending = array_slice(array_keys($newsActivity), 0, $trendingCount);
        $selected = array_merge($selected, $trending);

        // Add some random countries for diversity
        $remaining = array_diff($allCountries, $selected);
        $random = [];
        $pickCount = min($randomCount, count($remaining));
        if ($pickCount > 0) {
            $random = array_rand(array_flip($remaining), $pickCount);
            if (!is_array($random)) {
                $random = [$random];
            }
        }
        $selected = array_merge($selected, $random);

        return array_slice($selected, 0, $count);
    }

    /**
     * Select balanced countries
     */
    protected function selectBalancedCountries(int $count): array
    {
        $allCountries = array_keys(config('countries.countries', []));
        
        // Get current agent distribution
        $distribution = AiAgent::select('country', DB::raw('count(*) as total'))
            ->groupBy('country')
            ->pluck('total', 'country')
            ->toArray();

        // Sort countries by least agents
        $sorted = [];
        foreach ($allCountries as $country) {
            $sorted[$country] = $distribution[$country] ?? 0;
        }
        asort($sorted);

        return array_slice(array_keys($sorted), 0, $count);
    }

    /**
     * Select random countries
     */
    protected function selectRandomCountries(int $count): array
    {
        $allCountries = array_keys(config('countries.countries', []));
        $selected = array_rand(array_flip($allCountries), min($count, count($allCountries)));
        
        if (!is_array($selected)) {
            $selected = [$selected];
        }

        return $selected;
    }

    /**
     * Create single agent
     */
    protected function createAgent(string $country): AiAgent
    {
        $personalities = $this->buildPersonalityCycle();
        return $this->createAgentForCountryTopic($country, $personalities[array_rand($personalities)]);
    }

    protected function buildPersonalityCycle(): array
    {
        $cycle = ['political', 'sports', 'tech', 'entertainment', 'general', 'troll'];
        shuffle($cycle);
        return $cycle;
    }

    /**
     * Create a single agent for a deterministic country-topic pair.
     */
    public function createAgentForCountryTopic(string $country, string $topic): AiAgent
    {
        $personality = $this->normalizeTopicToPersonality($topic);
        $selectedLanguage = $this->getLanguageForCountry($country);

        // Generate identity
        $identity = $this->identityGenerator->generateIdentity($country, $personality);
        [$firstName, $lastName] = $this->splitName($identity['name'] ?? '');

        // Create user account
        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $this->identityGenerator->generateUsername($identity['name']),
            'email' => strtolower(str_replace(' ', '.', $identity['name'])) . rand(100, 999) . '@aiagent.local',
            'password' => bcrypt(Str::random(32)),
            'type' => UserType::AI_AGENT->value,
            'status' => UserStatus::ACTIVE->value,
            'country' => $country,
            'language' => $selectedLanguage,
            'tips' => [],
            'email_verified_at' => now(),
        ]);

        // Create AI agent
        $agentData = [
            'language' => $selectedLanguage,
            'user_id' => $user->id,
            'personality_type' => $personality,
            'country' => $country,
            'ai_provider' => $this->resolveDefaultAiProvider(),
            'image_provider' => $this->resolveDefaultImageProvider(),
            'posting_frequency' => rand(3, 8),
            'is_active' => true,
            'auto_created' => true,
            'account_created_at' => now(),
            'warm_up_stage' => 'day1',
            'age' => $identity['age'],
            'city' => $identity['city'],
            'date_of_birth' => $identity['date_of_birth'],
            'topics' => $identity['interests'],
            // Part 1: New Identity Fields
            'profession' => $identity['profession'],
            'political_leaning' => $identity['political_leaning'],
            'writing_style' => $identity['writing_style'],
            'editorial_tone' => $identity['editorial_tone'],
        ];

        $agentColumns = Schema::getColumnListing('ai_agents');
        if (!in_array('user_id', $agentColumns, true)) {
            throw new \RuntimeException("ai_agents.user_id column is missing. Run pending AI agent migrations.");
        }

        $agentData = array_intersect_key($agentData, array_flip($agentColumns));
        $agent = AiAgent::create($agentData);

        // Update user bio
        $user->update(['bio' => $identity['bio']]);

        // Ensure avatar + cover media are assigned for every newly created agent.
        try {
            $avatarPath = $this->profileMediaGenerator->generateAvatar($agent);
            $coverPath = $this->profileMediaGenerator->generateCover($agent);
            $user->update([
                'avatar' => $avatarPath,
                'cover' => $coverPath,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Auto media assignment failed for AI agent', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $agent;
    }

    protected function normalizeTopicToPersonality(string $topic): string
    {
        $topic = strtolower(trim($topic));

        return match ($topic) {
            'politics', 'political', 'government', 'conflict', 'war', 'crime' => 'political',
            'technology', 'tech', 'science' => 'tech',
            'sports', 'sport' => 'sports',
            'entertainment', 'media', 'meme' => 'entertainment',
            'troll' => 'troll',
            default => 'general',
        };
    }

    /**
     * Get primary language for country
     */
    protected function getLanguageForCountry(string $country): string
    {
        $languages = array_values((array) config("countries.countries.{$country}.languages", ['en-US']));
        if (empty($languages)) {
            return 'en-US';
        }

        if (count($languages) === 1) {
            return (string) $languages[0];
        }

        // Prefer least-used language among existing agents in same country.
        $usage = AiAgent::query()
            ->where('country', strtoupper($country))
            ->whereIn('language', $languages)
            ->selectRaw('language, count(*) as c')
            ->groupBy('language')
            ->pluck('c', 'language')
            ->toArray();

        $leastUsed = null;
        $leastCount = PHP_INT_MAX;
        foreach ($languages as $lang) {
            $count = (int) ($usage[$lang] ?? 0);
            if ($count < $leastCount) {
                $leastCount = $count;
                $leastUsed = $lang;
            }
        }
        if ($leastUsed !== null && rand(1, 100) <= 80) {
            return (string) $leastUsed;
        }

        // Prefer a non-English local language in multilingual countries for diversity.
        $nonEnglish = array_values(array_filter($languages, function ($lang) {
            return !str_starts_with(strtolower((string) $lang), 'en-');
        }));

        if (!empty($nonEnglish) && rand(1, 100) <= 65) {
            return (string) $nonEnglish[array_rand($nonEnglish)];
        }

        return (string) $languages[array_rand($languages)];
    }

    protected function resolveDefaultAiProvider(): string
    {
        try {
            $value = DB::table('admin_settings')->where('key', 'ai_default_provider')->value('value');
            if (!empty($value)) {
                return (string) $value;
            }
        } catch (\Throwable $e) {
            // Fall through to file config.
        }

        return (string) config('ai-providers.default', 'groq');
    }

    protected function resolveDefaultImageProvider(): string
    {
        try {
            $value = DB::table('admin_settings')->where('key', 'image_default_provider')->value('value');
            if (!empty($value)) {
                return (string) $value;
            }
        } catch (\Throwable $e) {
            // Fall through to file config.
        }

        return (string) config('image-providers.default', 'ai_generated');
    }

    protected function parseBooleanSetting($value): ?bool
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'off', 'no', ''], true)) {
            return false;
        }

        return null;
    }

    protected function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName));
        $parts = array_values(array_filter($parts));

        if (empty($parts)) {
            return ['AI', 'Agent'];
        }

        if (count($parts) === 1) {
            return [$parts[0], 'Agent'];
        }

        $lastName = array_pop($parts);
        $firstName = implode(' ', $parts);

        return [$firstName, $lastName];
    }
}
