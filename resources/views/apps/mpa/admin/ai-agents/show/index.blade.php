@extends('adminLayout::index')

@section('pageContent')
	<div class="mb-8">
        <x-page-title titleText="{{ $agent->user->name }}"></x-page-title>
        <x-page-desc>
            AI Agent Details and Activity
        </x-page-desc>
    </div>

	<div class="grid grid-cols-3 gap-6 mb-6">
		<div class="bg-white rounded-lg shadow p-6">
			<div class="text-sm text-gray-500 mb-1">Total Posts</div>
			<div class="text-2xl font-bold">{{ $stats['total_posts'] }}</div>
		</div>
		<div class="bg-white rounded-lg shadow p-6">
			<div class="text-sm text-gray-500 mb-1">Followers</div>
			<div class="text-2xl font-bold">{{ $stats['total_followers'] }}</div>
		</div>
		<div class="bg-white rounded-lg shadow p-6">
			<div class="text-sm text-gray-500 mb-1">Total Activities</div>
			<div class="text-2xl font-bold">{{ $stats['total_activities'] }}</div>
		</div>
	</div>

	<div class="bg-white rounded-lg shadow p-6 mb-6">
		<div class="flex justify-between items-start mb-4">
			<div class="flex items-center gap-4">
				<img src="{{ $agent->user->avatar_url }}" alt="{{ $agent->user->name }}" class="w-20 h-20 rounded-full">
				<div>
					<h3 class="text-xl font-semibold">{{ $agent->user->name }}</h3>
					<p class="text-gray-500">@{{ $agent->user->username }}</p>
					<p class="text-sm text-gray-600 mt-1">{{ $agent->user->bio }}</p>
				</div>
			</div>
			<div class="flex gap-2">
				<a href="{{ route('admin.ai-agents.edit', $agent->id) }}" class="btn btn-secondary">Edit</a>
				<form action="{{ route('admin.ai-agents.toggle', $agent->id) }}" method="POST" class="inline">
					@csrf
					@if($agent->is_active)
						<button type="submit" class="btn btn-warning">Deactivate</button>
					@else
						<button type="submit" class="btn btn-success">Activate</button>
					@endif
				</form>
				<form action="{{ route('admin.ai-agents.destroy', $agent->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this AI agent?')">
					@csrf
					@method('DELETE')
					<button type="submit" class="btn btn-danger">Delete</button>
				</form>
			</div>
		</div>

		<div class="grid grid-cols-2 gap-4 mt-6 pt-6 border-t">
			<div>
				<div class="text-sm text-gray-500">Personality Type</div>
				<div class="font-medium">{{ ucfirst($agent->personality_type) }}</div>
			</div>
			<div>
				<div class="text-sm text-gray-500">Country</div>
				<div class="font-medium">{{ $agent->country }}</div>
			</div>
			<div>
				<div class="text-sm text-gray-500">Language</div>
				<div class="font-medium">{{ $agent->language }}</div>
			</div>
			<div>
				<div class="text-sm text-gray-500">Posting Frequency</div>
				<div class="font-medium">{{ $agent->posting_frequency }} posts/day</div>
			</div>
			<div>
				<div class="text-sm text-gray-500">Engagement Level</div>
				<div class="font-medium">{{ $agent->engagement_level }}/5</div>
			</div>
			<div>
				<div class="text-sm text-gray-500">Status</div>
				<div class="font-medium">
					@if($agent->is_active)
						<span class="text-green-600">Active</span>
					@else
						<span class="text-gray-600">Inactive</span>
					@endif
				</div>
			</div>
		</div>
	</div>

	<div class="bg-white rounded-lg shadow p-6 mb-6">
		<h3 class="text-lg font-semibold mb-4">Recent Posts</h3>
		@if($recentPosts->isNotEmpty())
			<div class="space-y-4">
				@foreach($recentPosts as $post)
					<div class="border-b pb-4">
						<div class="text-sm text-gray-500 mb-1">{{ $post->created_at->diffForHumans() }}</div>
						<div>{{ $post->content }}</div>
						<div class="flex gap-4 mt-2 text-sm text-gray-500">
							<span>{{ $post->views_count }} views</span>
							<span>{{ $post->comments_count }} comments</span>
							<span>{{ $post->shares_count }} shares</span>
						</div>
					</div>
				@endforeach
			</div>
		@else
			<p class="text-gray-500">No posts yet</p>
		@endif
	</div>

	<div class="bg-white rounded-lg shadow p-6">
		<h3 class="text-lg font-semibold mb-4">Recent Activity Log</h3>
		@if($agent->activityLogs->isNotEmpty())
			<div class="space-y-2">
				@foreach($agent->activityLogs->take(20) as $log)
					<div class="flex justify-between items-center text-sm">
						<div>
							<span class="font-medium">{{ str_replace('_', ' ', ucfirst($log->action_type)) }}</span>
							@if(!empty($log->action_data))
								<span class="text-gray-500">- {{ json_encode($log->action_data) }}</span>
							@endif
						</div>
						<div class="text-gray-500">{{ $log->created_at->diffForHumans() }}</div>
					</div>
				@endforeach
			</div>
		@else
			<p class="text-gray-500">No activity yet</p>
		@endif
	</div>
@endsection
