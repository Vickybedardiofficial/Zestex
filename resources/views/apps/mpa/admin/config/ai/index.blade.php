@extends('adminLayout::index')

@section('pageContent')
    <div class="max-w-5xl mx-auto">
        <div class="mb-6">
            <x-page-title titleText="AI & Image Providers"></x-page-title>
            <x-page-desc>
                Configure AI and Image providers. Add API keys to enable providers.
            </x-page-desc>
        </div>

        <form action="{{ route('admin.config.ai.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Auto-Creation Section -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-zinc-800">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">🤖 AI Agent Auto-Creation</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Automatically generate new AI agents based on trending locations.</p>
                </div>
                <div class="p-6">
                     <label class="flex items-center cursor-pointer">
                        <div class="relative">
                            <input type="hidden" name="auto_agent_creation_enabled" value="0">
                            <input type="checkbox" name="auto_agent_creation_enabled" value="1" class="sr-only peer" 
                                {{ ($settings['auto_agent_creation_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">Enable Auto-Creation</span>
                    </label>

                    <div class="mt-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="hidden" name="ai_engagement_enabled" value="0">
                            <input type="checkbox" name="ai_engagement_enabled" value="1" class="mr-2"
                                {{ ($settings['ai_engagement_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-300">Enable Engagement (Comment / Like / Repost / Share)</span>
                        </label>
                    </div>

                    <div class="mt-5">
                        <label class="flex items-center cursor-pointer">
                            <input type="hidden" name="country_window_auto_create_enabled" value="0">
                            <input type="checkbox" name="country_window_auto_create_enabled" value="1" class="mr-2"
                                {{ ($settings['country_window_auto_create_enabled'] ?? env('COUNTRY_WINDOW_AUTO_CREATE_ENABLED', '1')) == '1' ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-300">Enable Country Window Limits</span>
                        </label>
                    </div>

                    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Morning Min (per country)</label>
                            <input type="number" min="0" max="500" name="country_window_auto_create_morning_min"
                                value="{{ old('country_window_auto_create_morning_min', $settings['country_window_auto_create_morning_min'] ?? env('COUNTRY_WINDOW_AUTO_CREATE_MORNING_MIN', 5)) }}"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Morning Max (per country)</label>
                            <input type="number" min="0" max="500" name="country_window_auto_create_morning_max"
                                value="{{ old('country_window_auto_create_morning_max', $settings['country_window_auto_create_morning_max'] ?? env('COUNTRY_WINDOW_AUTO_CREATE_MORNING_MAX', 10)) }}"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Evening Min (per country)</label>
                            <input type="number" min="0" max="500" name="country_window_auto_create_evening_min"
                                value="{{ old('country_window_auto_create_evening_min', $settings['country_window_auto_create_evening_min'] ?? env('COUNTRY_WINDOW_AUTO_CREATE_EVENING_MIN', 5)) }}"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Evening Max (per country)</label>
                            <input type="number" min="0" max="500" name="country_window_auto_create_evening_max"
                                value="{{ old('country_window_auto_create_evening_max', $settings['country_window_auto_create_evening_max'] ?? env('COUNTRY_WINDOW_AUTO_CREATE_EVENING_MAX', 10)) }}"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100">
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-zinc-800">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">🤖 AI Providers</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Add API keys to enable providers</p>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Gemini -->
                    <div class="p-5 border-2 rounded-xl transition-all {{ in_array('gemini', $enabledAIProviders) ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/10' : 'border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800' }}">
                        <div class="flex items-center gap-3 mb-3">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Gemini</h4>
                            <span class="text-xs px-2 py-1 rounded-full {{ in_array('gemini', $enabledAIProviders) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-200 text-gray-600 dark:bg-zinc-700 dark:text-gray-400' }}">
                                {{ in_array('gemini', $enabledAIProviders) ? '✓ Enabled' : '○ Disabled' }}
                            </span>
                            <label class="ml-auto flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="ai_provider_enabled_gemini" value="0">
                                <input type="checkbox" name="ai_provider_enabled_gemini" value="1" class="mr-1" {{ ($settings['ai_provider_enabled_gemini'] ?? '1') == '1' ? 'checked' : '' }}>
                                ON
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Google AI • <a href="https://makersuite.google.com/app/apikey" target="_blank" class="text-blue-600 hover:underline">Get API Key</a></p>
                        <input type="text" name="gemini_api_key" value="{{ $apiKeys['gemini_api_key'] ?? '' }}" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-zinc-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="AIza...">
                    </div>

                    <!-- ChatGPT -->
                    <div class="p-5 border-2 rounded-xl transition-all {{ in_array('chatgpt', $enabledAIProviders) ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/10' : 'border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800' }}">
                        <div class="flex items-center gap-3 mb-3">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">ChatGPT</h4>
                            <span class="text-xs px-2 py-1 rounded-full {{ in_array('chatgpt', $enabledAIProviders) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-200 text-gray-600 dark:bg-zinc-700 dark:text-gray-400' }}">
                                {{ in_array('chatgpt', $enabledAIProviders) ? '✓ Enabled' : '○ Disabled' }}
                            </span>
                            <label class="ml-auto flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="ai_provider_enabled_chatgpt" value="0">
                                <input type="checkbox" name="ai_provider_enabled_chatgpt" value="1" class="mr-1" {{ ($settings['ai_provider_enabled_chatgpt'] ?? '1') == '1' ? 'checked' : '' }}>
                                ON
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">OpenAI • <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:underline">Get API Key</a></p>
                        <input type="text" name="openai_api_key" value="{{ $apiKeys['openai_api_key'] ?? '' }}" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-zinc-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="sk-...">
                    </div>

                    <!-- Claude -->
                    <div class="p-5 border-2 rounded-xl transition-all {{ in_array('claude', $enabledAIProviders) ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/10' : 'border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800' }}">
                        <div class="flex items-center gap-3 mb-3">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Claude</h4>
                            <span class="text-xs px-2 py-1 rounded-full {{ in_array('claude', $enabledAIProviders) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-200 text-gray-600 dark:bg-zinc-700 dark:text-gray-400' }}">
                                {{ in_array('claude', $enabledAIProviders) ? 'âœ“ Enabled' : 'â—‹ Disabled' }}
                            </span>
                            <label class="ml-auto flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="ai_provider_enabled_claude" value="0">
                                <input type="checkbox" name="ai_provider_enabled_claude" value="1" class="mr-1" {{ ($settings['ai_provider_enabled_claude'] ?? '1') == '1' ? 'checked' : '' }}>
                                ON
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Anthropic â€¢ <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-blue-600 hover:underline">Get API Key</a></p>
                        <input type="text" name="claude_api_key" value="{{ $apiKeys['claude_api_key'] ?? '' }}" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-zinc-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="sk-ant-...">
                    </div>

                    <!-- XAI -->
                    <div class="p-5 border-2 rounded-xl transition-all {{ in_array('xai', $enabledAIProviders) ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/10' : 'border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800' }}">
                        <div class="flex items-center gap-3 mb-3">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">XAI (Grok)</h4>
                            <span class="text-xs px-2 py-1 rounded-full {{ in_array('xai', $enabledAIProviders) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-200 text-gray-600 dark:bg-zinc-700 dark:text-gray-400' }}">
                                {{ in_array('xai', $enabledAIProviders) ? '✓ Enabled' : '○ Disabled' }}
                            </span>
                            <label class="ml-auto flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="ai_provider_enabled_xai" value="0">
                                <input type="checkbox" name="ai_provider_enabled_xai" value="1" class="mr-1" {{ ($settings['ai_provider_enabled_xai'] ?? '1') == '1' ? 'checked' : '' }}>
                                ON
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">xAI Grok • <a href="https://console.x.ai" target="_blank" class="text-blue-600 hover:underline">Get API Key</a></p>
                        <input type="text" name="xai_api_key" value="{{ $apiKeys['xai_api_key'] ?? '' }}" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-zinc-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="xai-...">
                    </div>

                    <!-- Groq -->
                    <div class="p-5 border-2 rounded-xl transition-all {{ in_array('groq', $enabledAIProviders) ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/10' : 'border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800' }}">
                        <div class="flex items-center gap-3 mb-3">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Groq</h4>
                            <span class="text-xs px-2 py-1 rounded-full {{ in_array('groq', $enabledAIProviders) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-200 text-gray-600 dark:bg-zinc-700 dark:text-gray-400' }}">
                                {{ in_array('groq', $enabledAIProviders) ? '✓ Enabled' : '○ Disabled' }}
                            </span>
                            <label class="ml-auto flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="ai_provider_enabled_groq" value="0">
                                <input type="checkbox" name="ai_provider_enabled_groq" value="1" class="mr-1" {{ ($settings['ai_provider_enabled_groq'] ?? '1') == '1' ? 'checked' : '' }}>
                                ON
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Groq LLaMA • <a href="https://console.groq.com/keys" target="_blank" class="text-blue-600 hover:underline">Get API Key</a></p>
                        <input type="text" name="groq_api_key" value="{{ $apiKeys['groq_api_key'] ?? '' }}" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-zinc-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="gsk_...">
                    </div>

                    <!-- OpenRouter -->
                    <div class="p-5 border-2 rounded-xl transition-all {{ in_array('openrouter', $enabledAIProviders) ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/10' : 'border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800' }}">
                        <div class="flex items-center gap-3 mb-3">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">OpenRouter</h4>
                            <span class="text-xs px-2 py-1 rounded-full {{ in_array('openrouter', $enabledAIProviders) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-200 text-gray-600 dark:bg-zinc-700 dark:text-gray-400' }}">
                                {{ in_array('openrouter', $enabledAIProviders) ? '✓ Enabled' : '○ Disabled' }}
                            </span>
                            <label class="ml-auto flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="ai_provider_enabled_openrouter" value="0">
                                <input type="checkbox" name="ai_provider_enabled_openrouter" value="1" class="mr-1" {{ ($settings['ai_provider_enabled_openrouter'] ?? '1') == '1' ? 'checked' : '' }}>
                                ON
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Multi-model • <a href="https://openrouter.ai/keys" target="_blank" class="text-blue-600 hover:underline">Get API Key</a></p>
                        <input type="text" name="openrouter_api_key" value="{{ $apiKeys['openrouter_api_key'] ?? '' }}" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-zinc-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="sk-or-...">
                    </div>

                    <!-- AIMLAPI -->
                    <div class="p-5 border-2 rounded-xl transition-all {{ in_array('aimlapi', $enabledAIProviders) ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/10' : 'border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800' }}">
                        <div class="flex items-center gap-3 mb-3">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">AIMLAPI</h4>
                            <span class="text-xs px-2 py-1 rounded-full {{ in_array('aimlapi', $enabledAIProviders) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-200 text-gray-600 dark:bg-zinc-700 dark:text-gray-400' }}">
                                {{ in_array('aimlapi', $enabledAIProviders) ? '✓ Enabled' : '○ Disabled' }}
                            </span>
                            <label class="ml-auto flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="ai_provider_enabled_aimlapi" value="0">
                                <input type="checkbox" name="ai_provider_enabled_aimlapi" value="1" class="mr-1" {{ ($settings['ai_provider_enabled_aimlapi'] ?? '1') == '1' ? 'checked' : '' }}>
                                ON
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">AI/ML API • <a href="https://aimlapi.com" target="_blank" class="text-blue-600 hover:underline">Get API Key</a></p>
                        <input type="text" name="aimlapi_api_key" value="{{ $apiKeys['aimlapi_api_key'] ?? '' }}" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-zinc-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="...">
                    </div>

                    @if(!empty($enabledAIProviders))
                    <div class="mt-6 p-5 bg-blue-50 dark:bg-blue-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800">
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2 block">Default AI Provider</span>
                            <select name="ai_default_provider" class="w-full px-4 py-2.5 rounded-lg border-2 border-blue-300 dark:border-blue-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100">
                                @foreach($enabledAIProviders as $provider)
                                    <option value="{{ $provider }}" {{ ($settings['ai_default_provider'] ?? env('AI_DEFAULT_PROVIDER', 'xai')) == $provider ? 'selected' : '' }}>
                                        {{ ucfirst($provider) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Image Providers Section -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-zinc-800">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">🖼️ Image Providers</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Add API keys for stock photos</p>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Pexels -->
                    <div class="p-5 border-2 rounded-xl transition-all {{ in_array('pexels', $enabledImageProviders) ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/10' : 'border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800' }}">
                        <div class="flex items-center gap-3 mb-3">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pexels</h4>
                            <span class="text-xs px-2 py-1 rounded-full {{ in_array('pexels', $enabledImageProviders) ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-200 text-gray-600 dark:bg-zinc-700 dark:text-gray-400' }}">
                                {{ in_array('pexels', $enabledImageProviders) ? '✓ Enabled' : '○ Disabled' }}
                            </span>
                            <label class="ml-auto flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="image_provider_enabled_pexels" value="0">
                                <input type="checkbox" name="image_provider_enabled_pexels" value="1" class="mr-1" {{ ($settings['image_provider_enabled_pexels'] ?? '1') == '1' ? 'checked' : '' }}>
                                ON
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Free photos • <a href="https://www.pexels.com/api/" target="_blank" class="text-blue-600 hover:underline">Get API Key</a></p>
                        <input type="text" name="pexels_api_key" value="{{ $apiKeys['pexels_api_key'] ?? '' }}" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-zinc-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="...">
                    </div>

                    <!-- Unsplash -->
                    <div class="p-5 border-2 rounded-xl transition-all {{ in_array('unsplash', $enabledImageProviders) ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/10' : 'border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800' }}">
                        <div class="flex items-center gap-3 mb-3">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Unsplash</h4>
                            <span class="text-xs px-2 py-1 rounded-full {{ in_array('unsplash', $enabledImageProviders) ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-200 text-gray-600 dark:bg-zinc-700 dark:text-gray-400' }}">
                                {{ in_array('unsplash', $enabledImageProviders) ? '✓ Enabled' : '○ Disabled' }}
                            </span>
                            <label class="ml-auto flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="image_provider_enabled_unsplash" value="0">
                                <input type="checkbox" name="image_provider_enabled_unsplash" value="1" class="mr-1" {{ ($settings['image_provider_enabled_unsplash'] ?? '1') == '1' ? 'checked' : '' }}>
                                ON
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">HD photos • <a href="https://unsplash.com/developers" target="_blank" class="text-blue-600 hover:underline">Get API Key</a></p>
                        <input type="text" name="unsplash_api_key" value="{{ $apiKeys['unsplash_api_key'] ?? '' }}" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-zinc-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="...">
                    </div>

                    <!-- Pixabay -->
                    <div class="p-5 border-2 rounded-xl transition-all {{ in_array('pixabay', $enabledImageProviders) ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/10' : 'border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800' }}">
                        <div class="flex items-center gap-3 mb-3">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pixabay</h4>
                            <span class="text-xs px-2 py-1 rounded-full {{ in_array('pixabay', $enabledImageProviders) ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-200 text-gray-600 dark:bg-zinc-700 dark:text-gray-400' }}">
                                {{ in_array('pixabay', $enabledImageProviders) ? '✓ Enabled' : '○ Disabled' }}
                            </span>
                            <label class="ml-auto flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="image_provider_enabled_pixabay" value="0">
                                <input type="checkbox" name="image_provider_enabled_pixabay" value="1" class="mr-1" {{ ($settings['image_provider_enabled_pixabay'] ?? '1') == '1' ? 'checked' : '' }}>
                                ON
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Free media • <a href="https://pixabay.com/api/docs/" target="_blank" class="text-blue-600 hover:underline">Get API Key</a></p>
                        <input type="text" name="pixabay_api_key" value="{{ $apiKeys['pixabay_api_key'] ?? '' }}" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-zinc-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="...">
                    </div>

                    @if(!empty($enabledImageProviders))
                    <div class="mt-6 p-5 bg-blue-50 dark:bg-blue-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800">
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2 block">Default Image Provider</span>
                            <select name="image_default_provider" class="w-full px-4 py-2.5 rounded-lg border-2 border-blue-300 dark:border-blue-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100">
                                @foreach($enabledImageProviders as $provider)
                                    <option value="{{ $provider }}" {{ ($settings['image_default_provider'] ?? env('IMAGE_DEFAULT_PROVIDER', 'pexels')) == $provider ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $provider)) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Feed Ranking Controls -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-zinc-800">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Feed Ranking Controls</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Tune scoring, recommendation and trending thresholds without code changes.</p>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Sort</label>
                            <select name="feed_ranking_default_sort" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100">
                                @foreach(['hot','new','top','rising','controversial','best'] as $sortOpt)
                                    <option value="{{ $sortOpt }}" {{ old('feed_ranking_default_sort', $settings['feed_ranking_default_sort'] ?? config('feed-ranking.default_sort', 'hot')) === $sortOpt ? 'selected' : '' }}>
                                        {{ ucfirst($sortOpt) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Wilson Z</label>
                            <input type="number" step="0.01" min="1" max="3" name="feed_ranking_wilson_z"
                                   value="{{ old('feed_ranking_wilson_z', $settings['feed_ranking_wilson_z'] ?? config('feed-ranking.wilson_z', 1.96)) }}"
                                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100">
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Eligibility Thresholds</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <input type="number" step="0.1" name="feed_ranking_thresholds_engagement_minimum_for_recommendation" value="{{ old('feed_ranking_thresholds_engagement_minimum_for_recommendation', $settings['feed_ranking_thresholds_engagement_minimum_for_recommendation'] ?? config('feed-ranking.thresholds.engagement.minimum_for_recommendation', 20)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Min engagement for recommendation">
                            <input type="number" step="0.1" name="feed_ranking_thresholds_engagement_minimum_for_trending" value="{{ old('feed_ranking_thresholds_engagement_minimum_for_trending', $settings['feed_ranking_thresholds_engagement_minimum_for_trending'] ?? config('feed-ranking.thresholds.engagement.minimum_for_trending', 45)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Min engagement for trending">
                            <input type="number" step="0.01" name="feed_ranking_thresholds_best_minimum_for_recommendation" value="{{ old('feed_ranking_thresholds_best_minimum_for_recommendation', $settings['feed_ranking_thresholds_best_minimum_for_recommendation'] ?? config('feed-ranking.thresholds.best.minimum_for_recommendation', 0.45)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Min best for recommendation">
                            <input type="number" step="0.01" name="feed_ranking_thresholds_best_minimum_for_trending" value="{{ old('feed_ranking_thresholds_best_minimum_for_trending', $settings['feed_ranking_thresholds_best_minimum_for_trending'] ?? config('feed-ranking.thresholds.best.minimum_for_trending', 0.60)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Min best for trending">
                            <input type="number" step="0.1" name="feed_ranking_thresholds_hot_minimum_for_recommendation" value="{{ old('feed_ranking_thresholds_hot_minimum_for_recommendation', $settings['feed_ranking_thresholds_hot_minimum_for_recommendation'] ?? config('feed-ranking.thresholds.hot.minimum_for_recommendation', 40)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Min hot for recommendation">
                            <input type="number" step="0.1" name="feed_ranking_thresholds_hot_minimum_for_trending" value="{{ old('feed_ranking_thresholds_hot_minimum_for_trending', $settings['feed_ranking_thresholds_hot_minimum_for_trending'] ?? config('feed-ranking.thresholds.hot.minimum_for_trending', 75)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Min hot for trending">
                            <input type="number" step="0.1" name="feed_ranking_thresholds_rising_minimum_for_recommendation" value="{{ old('feed_ranking_thresholds_rising_minimum_for_recommendation', $settings['feed_ranking_thresholds_rising_minimum_for_recommendation'] ?? config('feed-ranking.thresholds.rising.minimum_for_recommendation', 20)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Min rising for recommendation">
                            <input type="number" step="0.1" name="feed_ranking_thresholds_rising_minimum_for_viral_watch" value="{{ old('feed_ranking_thresholds_rising_minimum_for_viral_watch', $settings['feed_ranking_thresholds_rising_minimum_for_viral_watch'] ?? config('feed-ranking.thresholds.rising.minimum_for_viral_watch', 60)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Min rising for viral watch">
                            <input type="number" step="0.1" name="feed_ranking_thresholds_rising_minimum_for_trending" value="{{ old('feed_ranking_thresholds_rising_minimum_for_trending', $settings['feed_ranking_thresholds_rising_minimum_for_trending'] ?? config('feed-ranking.thresholds.rising.minimum_for_trending', 100)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Min rising for trending">
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Geo/Language Boost</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <input type="number" step="0.01" name="feed_ranking_context_country_match_boost" value="{{ old('feed_ranking_context_country_match_boost', $settings['feed_ranking_context_country_match_boost'] ?? config('feed-ranking.context.country_match_boost', 0.12)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Country boost">
                            <input type="number" step="0.01" name="feed_ranking_context_city_match_boost" value="{{ old('feed_ranking_context_city_match_boost', $settings['feed_ranking_context_city_match_boost'] ?? config('feed-ranking.context.city_match_boost', 0.08)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="City boost">
                            <input type="number" step="0.01" name="feed_ranking_context_area_match_boost" value="{{ old('feed_ranking_context_area_match_boost', $settings['feed_ranking_context_area_match_boost'] ?? config('feed-ranking.context.area_match_boost', 0.06)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Area boost">
                            <input type="number" step="0.01" name="feed_ranking_context_cross_country_multiplier" value="{{ old('feed_ranking_context_cross_country_multiplier', $settings['feed_ranking_context_cross_country_multiplier'] ?? config('feed-ranking.context.cross_country_multiplier', 0.90)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Cross-country multiplier">
                            <input type="number" step="0.01" name="feed_ranking_context_language_match_multiplier" value="{{ old('feed_ranking_context_language_match_multiplier', $settings['feed_ranking_context_language_match_multiplier'] ?? config('feed-ranking.context.language_match_multiplier', 1.08)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Language match multiplier">
                            <input type="number" step="0.01" name="feed_ranking_context_language_mismatch_multiplier" value="{{ old('feed_ranking_context_language_mismatch_multiplier', $settings['feed_ranking_context_language_mismatch_multiplier'] ?? config('feed-ranking.context.language_mismatch_multiplier', 0.95)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Language mismatch multiplier">
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">New Creator Boost</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="feed_ranking_new_creator_enabled" value="0">
                                <input type="checkbox" name="feed_ranking_new_creator_enabled" value="1" class="mr-2" {{ old('feed_ranking_new_creator_enabled', $settings['feed_ranking_new_creator_enabled'] ?? (config('feed-ranking.new_creator.enabled', true) ? '1' : '0')) == '1' ? 'checked' : '' }}>
                                Enable New Creator Boost
                            </label>
                            <input type="number" min="1" max="720" name="feed_ranking_new_creator_max_account_age_hours" value="{{ old('feed_ranking_new_creator_max_account_age_hours', $settings['feed_ranking_new_creator_max_account_age_hours'] ?? config('feed-ranking.new_creator.max_account_age_hours', 72)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Max account age hours">
                            <input type="number" step="0.01" min="0" max="3" name="feed_ranking_new_creator_boost_multiplier" value="{{ old('feed_ranking_new_creator_boost_multiplier', $settings['feed_ranking_new_creator_boost_multiplier'] ?? config('feed-ranking.new_creator.boost_multiplier', 1.12)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Boost multiplier">
                            <input type="number" step="0.01" min="0" max="1" name="feed_ranking_new_creator_quality_gate_best_score" value="{{ old('feed_ranking_new_creator_quality_gate_best_score', $settings['feed_ranking_new_creator_quality_gate_best_score'] ?? config('feed-ranking.new_creator.quality_gate_best_score', 0.35)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Quality gate best score">
                            <input type="number" step="0.1" min="0" name="feed_ranking_new_creator_quality_gate_engagement" value="{{ old('feed_ranking_new_creator_quality_gate_engagement', $settings['feed_ranking_new_creator_quality_gate_engagement'] ?? config('feed-ranking.new_creator.quality_gate_engagement', 8)) }}" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-gray-100" placeholder="Quality gate engagement">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition-all">
                    💾 Save All Settings
                </button>
            </div>
        </form>
    </div>
@endsection

