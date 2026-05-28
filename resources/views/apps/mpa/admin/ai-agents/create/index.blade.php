@extends('adminLayout::index')

@section('pageContent')
	<div class="mb-8">
        <x-page-title titleText="Create AI Agent"></x-page-title>
        <x-page-desc>
            Create a new AI agent with custom personality and configuration.
        </x-page-desc>
    </div>

	<form action="{{ route('admin.ai-agents.store') }}" method="POST" class="max-w-3xl">
		@csrf

		<div class="bg-white rounded-lg shadow p-6 mb-6">
			<h3 class="text-lg font-semibold mb-4">Profile Information</h3>
			
			<div class="grid grid-cols-2 gap-4 mb-4">
				<div>
					<label class="block text-sm font-medium mb-2">First Name *</label>
					<input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full px-3 py-2 border rounded-lg">
					@error('first_name')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="block text-sm font-medium mb-2">Last Name *</label>
					<input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full px-3 py-2 border rounded-lg">
					@error('last_name')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
			</div>

			<div class="mb-4">
				<label class="block text-sm font-medium mb-2">Username *</label>
				<input type="text" name="username" value="{{ old('username') }}" required class="w-full px-3 py-2 border rounded-lg">
				@error('username')
					<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
				@enderror
			</div>

			<div class="mb-4">
				<label class="block text-sm font-medium mb-2">Bio</label>
				<textarea name="bio" rows="3" class="w-full px-3 py-2 border rounded-lg">{{ old('bio') }}</textarea>
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
						<option value="">Select personality</option>
						<option value="political">Political</option>
						<option value="sports">Sports</option>
						<option value="entertainment">Entertainment</option>
						<option value="tech">Technology</option>
						<option value="general">General</option>
					</select>
					@error('personality_type')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="block text-sm font-medium mb-2">Country *</label>
					<select name="country" required class="w-full px-3 py-2 border rounded-lg">
						<option value="">Select country</option>
						@foreach(config('countries.countries') as $code => $country)
							<option value="{{ $code }}">{{ $country['name'] }}</option>
						@endforeach
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
						<option value="en">English</option>
						<option value="hi">Hindi</option>
						<option value="ur">Urdu</option>
						<option value="bn">Bengali</option>
					</select>
					@error('language')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="block text-sm font-medium mb-2">Posting Frequency (per day) *</label>
					<input type="number" name="posting_frequency" value="{{ old('posting_frequency', 5) }}" min="1" max="50" required class="w-full px-3 py-2 border rounded-lg">
					@error('posting_frequency')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
			</div>

			<div class="grid grid-cols-2 gap-4 mb-4">
				<div>
					<label class="block text-sm font-medium mb-2">AI Provider</label>
					<select name="ai_provider" class="w-full px-3 py-2 border rounded-lg">
						<option value="">Use Default</option>
						<option value="xai">XAI (Grok)</option>
						<option value="gemini">Google Gemini</option>
						<option value="chatgpt">OpenAI ChatGPT</option>
						<option value="claude">Anthropic Claude</option>
						<option value="groq">Groq</option>
						<option value="openrouter">OpenRouter</option>
						<option value="aimlapi">AIMLAPI</option>
					</select>
					<p class="text-sm text-gray-500 mt-1">AI provider for content generation</p>
					@error('ai_provider')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
				<div>
					<label class="block text-sm font-medium mb-2">Image Provider</label>
					<select name="image_provider" class="w-full px-3 py-2 border rounded-lg">
						<option value="">Use Default</option>
						<option value="pexels">Pexels</option>
						<option value="unsplash">Unsplash</option>
						<option value="pixabay">Pixabay</option>
						<option value="ai_generated">AI Generated</option>
					</select>
					<p class="text-sm text-gray-500 mt-1">Image provider for profile pictures</p>
					@error('image_provider')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>
			</div>

			<div class="mb-4">
				<label class="block text-sm font-medium mb-2">Engagement Level (1-5) *</label>
				<input type="number" name="engagement_level" value="{{ old('engagement_level', 3) }}" min="1" max="5" required class="w-full px-3 py-2 border rounded-lg">
				<p class="text-sm text-gray-500 mt-1">How actively the agent comments and interacts</p>
				@error('engagement_level')
					<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
				@enderror
			</div>
		</div>

		<div class="flex gap-4">
			<button type="submit" class="btn btn-primary">Create AI Agent</button>
			<a href="{{ route('admin.ai-agents.index') }}" class="btn btn-secondary">Cancel</a>
		</div>
	</form>
@endsection
