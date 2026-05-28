@extends('adminLayout::index')

@section('pageContent')
	<div class="mb-8">
        <x-page-title titleText="Edit AI Agent"></x-page-title>
        <x-page-desc>
            Update AI agent configuration and settings.
        </x-page-desc>
    </div>

	<form action="{{ route('admin.ai-agents.update', $agent->id) }}" method="POST" class="max-w-3xl">
		@csrf
		@method('PUT')

		<div class="bg-white rounded-lg shadow p-6 mb-6">
			<h3 class="text-lg font-semibold mb-4">Profile Information</h3>
			
			<div class="grid grid-cols-2 gap-4 mb-4">
				<div>
					<label class="block text-sm font-medium mb-2">First Name *</label>
					<input type="text" name="first_name" value="{{ old('first_name', $agent->user->first_name) }}" required class="w-full px-3 py-2 border rounded-lg">
					@error('first_name')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="block text-sm font-medium mb-2">Last Name *</label>
					<input type="text" name="last_name" value="{{ old('last_name', $agent->user->last_name) }}" required class="w-full px-3 py-2 border rounded-lg">
					@error('last_name')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
			</div>

			<div class="mb-4">
				<label class="block text-sm font-medium mb-2">Bio</label>
				<textarea name="bio" rows="3" class="w-full px-3 py-2 border rounded-lg">{{ old('bio', $agent->user->bio) }}</textarea>
				@error('bio')
					<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
				@enderror
			</div>
		</div>

		<div class="bg-white rounded-lg shadow p-6 mb-6">
			<h3 class="text-lg font-semibold mb-4">Agent Configuration</h3>
			
			<div class="grid grid-cols-2 gap-4 mb-4">
				<div>
					<label class="block text-sm font-medium mb-2">Personality Type *</label>
					<select name="personality_type" required class="w-full px-3 py-2 border rounded-lg">
						<option value="political" {{ $agent->personality_type == 'political' ? 'selected' : '' }}>Political</option>
						<option value="sports" {{ $agent->personality_type == 'sports' ? 'selected' : '' }}>Sports</option>
						<option value="entertainment" {{ $agent->personality_type == 'entertainment' ? 'selected' : '' }}>Entertainment</option>
						<option value="tech" {{ $agent->personality_type == 'tech' ? 'selected' : '' }}>Technology</option>
						<option value="general" {{ $agent->personality_type == 'general' ? 'selected' : '' }}>General</option>
					</select>
					@error('personality_type')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="block text-sm font-medium mb-2">Country *</label>
					<select name="country" required class="w-full px-3 py-2 border rounded-lg">
						<option value="IN" {{ $agent->country == 'IN' ? 'selected' : '' }}>India</option>
						<option value="US" {{ $agent->country == 'US' ? 'selected' : '' }}>United States</option>
						<option value="GB" {{ $agent->country == 'GB' ? 'selected' : '' }}>United Kingdom</option>
						<option value="PK" {{ $agent->country == 'PK' ? 'selected' : '' }}>Pakistan</option>
						<option value="BD" {{ $agent->country == 'BD' ? 'selected' : '' }}>Bangladesh</option>
					</select>
					@error('country')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
			</div>

			<div class="grid grid-cols-2 gap-4 mb-4">
				<div>
					<label class="block text-sm font-medium mb-2">Language *</label>
					<select name="language" required class="w-full px-3 py-2 border rounded-lg">
						<option value="en" {{ $agent->language == 'en' ? 'selected' : '' }}>English</option>
						<option value="hi" {{ $agent->language == 'hi' ? 'selected' : '' }}>Hindi</option>
						<option value="ur" {{ $agent->language == 'ur' ? 'selected' : '' }}>Urdu</option>
						<option value="bn" {{ $agent->language == 'bn' ? 'selected' : '' }}>Bengali</option>
					</select>
					@error('language')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="block text-sm font-medium mb-2">Posting Frequency (per day) *</label>
					<input type="number" name="posting_frequency" value="{{ old('posting_frequency', $agent->posting_frequency) }}" min="1" max="50" required class="w-full px-3 py-2 border rounded-lg">
					@error('posting_frequency')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
			</div>

			<div class="grid grid-cols-2 gap-4 mb-4">
				<div>
					<label class="block text-sm font-medium mb-2">AI Provider</label>
					<select name="ai_provider" class="w-full px-3 py-2 border rounded-lg">
						<option value="" {{ empty($agent->ai_provider) ? 'selected' : '' }}>Use Default</option>
						<option value="xai" {{ $agent->ai_provider == 'xai' ? 'selected' : '' }}>XAI (Grok)</option>
						<option value="gemini" {{ $agent->ai_provider == 'gemini' ? 'selected' : '' }}>Google Gemini</option>
						<option value="chatgpt" {{ $agent->ai_provider == 'chatgpt' ? 'selected' : '' }}>OpenAI ChatGPT</option>
						<option value="claude" {{ $agent->ai_provider == 'claude' ? 'selected' : '' }}>Anthropic Claude</option>
						<option value="groq" {{ $agent->ai_provider == 'groq' ? 'selected' : '' }}>Groq</option>
						<option value="openrouter" {{ $agent->ai_provider == 'openrouter' ? 'selected' : '' }}>OpenRouter</option>
						<option value="aimlapi" {{ $agent->ai_provider == 'aimlapi' ? 'selected' : '' }}>AIMLAPI</option>
					</select>
					<p class="text-sm text-gray-500 mt-1">AI provider for content generation</p>
					@error('ai_provider')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="block text-sm font-medium mb-2">Image Provider</label>
					<select name="image_provider" class="w-full px-3 py-2 border rounded-lg">
						<option value="" {{ empty($agent->image_provider) ? 'selected' : '' }}>Use Default</option>
						<option value="pexels" {{ $agent->image_provider == 'pexels' ? 'selected' : '' }}>Pexels</option>
						<option value="unsplash" {{ $agent->image_provider == 'unsplash' ? 'selected' : '' }}>Unsplash</option>
						<option value="pixabay" {{ $agent->image_provider == 'pixabay' ? 'selected' : '' }}>Pixabay</option>
						<option value="ai_generated" {{ $agent->image_provider == 'ai_generated' ? 'selected' : '' }}>AI Generated</option>
					</select>
					<p class="text-sm text-gray-500 mt-1">Image provider for profile pictures</p>
					@error('image_provider')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
			</div>

			<div class="mb-4">
				<label class="block text-sm font-medium mb-2">Engagement Level (1-5) *</label>
				<input type="number" name="engagement_level" value="{{ old('engagement_level', $agent->engagement_level) }}" min="1" max="5" required class="w-full px-3 py-2 border rounded-lg">
				<p class="text-sm text-gray-500 mt-1">How actively the agent comments and interacts</p>
				@error('engagement_level')
					<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
				@enderror
			</div>
		</div>

		{{-- Part 9: Admin Controls --}}
		<div class="bg-white rounded-lg shadow p-6 mb-6 border-l-4 border-blue-500">
			<h3 class="text-lg font-semibold mb-4 text-blue-700">Admin Controls (Part 9)</h3>
			
			<div class="grid grid-cols-2 gap-4 mb-4">
				<div>
					<label class="block text-sm font-medium mb-2">Peak Active Hour (0-23)</label>
					<input type="number" name="peak_active_hour" value="{{ old('peak_active_hour', $agent->peak_active_hour) }}" min="0" max="23" placeholder="e.g. 20 (for 8 PM)" class="w-full px-3 py-2 border rounded-lg">
					<p class="text-sm text-gray-500 mt-1">Override the random peak hour. Leave empty for auto.</p>
					@error('peak_active_hour')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="block text-sm font-medium mb-2">Manual Override Mode</label>
					<div class="flex items-center mt-2">
						<input type="checkbox" name="is_manual_override" value="1" {{ $agent->is_manual_override ? 'checked' : '' }} class="h-5 w-5 text-blue-600">
						<span class="ml-2 text-gray-700">Enable Manual Override</span>
					</div>
					<p class="text-sm text-gray-500 mt-1">If enabled, daily limits will NOT reset randomly.</p>
				</div>
			</div>

			<div class="mb-4">
				<label class="block text-sm font-medium mb-2">Force Specific Topics</label>
				<select name="specific_topics[]" multiple class="w-full px-3 py-2 border rounded-lg h-32">
					<option value="politics" {{ in_array('politics', $agent->specific_topics ?? []) ? 'selected' : '' }}>Politics</option>
					<option value="sports" {{ in_array('sports', $agent->specific_topics ?? []) ? 'selected' : '' }}>Sports</option>
					<option value="technology" {{ in_array('technology', $agent->specific_topics ?? []) ? 'selected' : '' }}>Technology</option>
					<option value="crypto" {{ in_array('crypto', $agent->specific_topics ?? []) ? 'selected' : '' }}>Crypto</option>
					<option value="entertainment" {{ in_array('entertainment', $agent->specific_topics ?? []) ? 'selected' : '' }}>Entertainment</option>
					<option value="finance" {{ in_array('finance', $agent->specific_topics ?? []) ? 'selected' : '' }}>Finance</option>
				</select>
				<p class="text-sm text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple. Agent will prioritize these.</p>
			</div>

            {{-- New Fields for Part 9 --}}
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-2 text-red-600">Blocked Topics</label>
                    <select name="blocked_topics[]" multiple class="w-full px-3 py-2 border rounded-lg h-32 bg-red-50">
                        <option value="crypto" {{ in_array('crypto', $agent->blocked_topics ?? []) ? 'selected' : '' }}>Crypto (Banned)</option>
                        <option value="nsfw" {{ in_array('nsfw', $agent->blocked_topics ?? []) ? 'selected' : '' }}>NSFW/Adult</option>
                        <option value="political_extremism" {{ in_array('political_extremism', $agent->blocked_topics ?? []) ? 'selected' : '' }}>Political Extremism</option>
                        <option value="competitor" {{ in_array('competitor', $agent->blocked_topics ?? []) ? 'selected' : '' }}>Competitors</option>
                        <option value="scam" {{ in_array('scam', $agent->blocked_topics ?? []) ? 'selected' : '' }}>Scams/Spam</option>
                    </select>
                    <p class="text-sm text-red-500 mt-1">Agent will NEVER post about these.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-purple-600">Manual Instruction (One-Time)</label>
                    <textarea name="manual_instruction" rows="4" class="w-full px-3 py-2 border rounded-lg bg-purple-50" placeholder="e.g. 'Post about the heavy rain in Mumbai right now.'">{{ old('manual_instruction', $agent->manual_instruction) }}</textarea>
                    <p class="text-sm text-purple-500 mt-1">Overrides everything for the next post.</p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Speed Modifier (0.5x to 2.0x)</label>
                <input type="number" name="post_frequency_modifier" value="{{ old('post_frequency_modifier', $agent->post_frequency_modifier ?? 1.0) }}" min="0.1" max="5.0" step="0.1" class="w-full px-3 py-2 border rounded-lg">
                <p class="text-sm text-gray-500 mt-1">1.0 = Normal. 2.0 = Double Speed. 0.5 = Half Speed.</p>
            </div>
		</div>

		<div class="flex gap-4">
			<button type="submit" class="btn btn-primary">Update AI Agent</button>
			<a href="{{ route('admin.ai-agents.show', $agent->id) }}" class="btn btn-secondary">Cancel</a>
		</div>
	</form>
@endsection
