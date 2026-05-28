@extends('adminLayout::index')

@section('pageContent')
	<div class="mb-8">
        <x-page-title titleText="AI Agents"></x-page-title>
        <x-page-desc>
            Manage AI agents that interact with users on the platform. Create, configure, and monitor AI agent activities.
        </x-page-desc>
    </div>

	<div class="mb-4 flex justify-between items-center">
		<x-tabs.tabs>
			<x-tabs.tab-item :active="empty($filters['status'])" href="{{ route('admin.ai-agents.index') }}" textLabel="All"></x-tabs.tab-item>
			<x-tabs.tab-item :active="$filters['status'] == 'active'" href="{{ route('admin.ai-agents.index', ['status' => 'active']) }}" textLabel="Active"></x-tabs.tab-item>
			<x-tabs.tab-item :active="$filters['status'] == 'inactive'" href="{{ route('admin.ai-agents.index', ['status' => 'inactive']) }}" textLabel="Inactive"></x-tabs.tab-item>
		</x-tabs.tabs>
		
		<div class="flex items-center gap-4">
			<!-- Part 1: Admin Start/Stop Switch -->
			<form action="{{ route('admin.ai-agents.toggle-auto-creation') }}" method="POST" class="flex items-center bg-gray-100 px-4 py-2 rounded-lg border border-gray-300">
				@csrf
				<span class="mr-3 text-sm font-bold {{ $autoCreationEnabled ? 'text-green-600' : 'text-red-600' }}">
					Auto: {{ $autoCreationEnabled ? 'ON' : 'OFF' }}
				</span>
				<label class="relative inline-flex items-center cursor-pointer mr-2">
					<input type="checkbox" name="enabled" class="sr-only peer" onchange="this.form.submit()" {{ $autoCreationEnabled ? 'checked' : '' }}>
					<div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
				</label>
                <button type="submit" class="text-xs bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded text-gray-700">Update</button>
			</form>

			<form action="{{ route('admin.ai-agents.toggle-engagement') }}" method="POST" class="flex items-center bg-gray-100 px-4 py-2 rounded-lg border border-gray-300">
				@csrf
				<span class="mr-3 text-sm font-bold {{ $engagementEnabled ? 'text-green-600' : 'text-red-600' }}">
					Engage: {{ $engagementEnabled ? 'ON' : 'OFF' }}
				</span>
				<label class="relative inline-flex items-center cursor-pointer mr-2">
					<input type="checkbox" name="enabled" class="sr-only peer" onchange="this.form.submit()" {{ $engagementEnabled ? 'checked' : '' }}>
					<div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
				</label>
                <button type="submit" class="text-xs bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded text-gray-700">Update</button>
			</form>

			<a href="{{ route('admin.ai-agents.create') }}" class="btn btn-primary">
				<i class="ri-add-line"></i> Create AI Agent
			</a>
		</div>
	</div>

	<x-table.table>
		<x-slot:filter>
			<div class="mb-4">
				<form action="{{ route('admin.ai-agents.index') }}" method="GET">
					<x-search.searchbar :value="$filters['search']" :cancelUrl="route('admin.ai-agents.index')" />
					<div class="mt-1">
						<x-search.desc description="Search by agent name or username" />
					</div>
				</form>
			</div>
		</x-slot:filter>
		<x-table.thead>
			<x-table.th>Agent</x-table.th>
			<x-table.th>Personality</x-table.th>
			<x-table.th>Country</x-table.th>
			<x-table.th>Status</x-table.th>
			<x-table.th>Posts</x-table.th>
			<x-table.th>Last Activity</x-table.th>
			<x-table.th>Actions</x-table.th>
		</x-table.thead>
		<x-table.tbody>
			@if($agents->isNotEmpty())
				@foreach ($agents as $agent)
					<x-table.tr>
						<x-table.td>
							<div class="flex items-center gap-3">
								<img src="{{ $agent->user->avatar_url }}" alt="{{ $agent->user->name }}" class="w-10 h-10 rounded-full">
								<div>
									<div class="font-medium">{{ $agent->user->name }}</div>
									<div class="text-sm text-gray-500">@{{ $agent->user->username }}</div>
								</div>
							</div>
						</x-table.td>
						<x-table.td>
							<span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
								{{ ucfirst($agent->personality_type) }}
							</span>
						</x-table.td>
						<x-table.td>{{ $agent->country }}</x-table.td>
						<x-table.td>
							@if($agent->is_active)
								<span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Active</span>
							@else
								<span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800">Inactive</span>
							@endif
						</x-table.td>
						<x-table.td>{{ $agent->user->publications_count }}</x-table.td>
						<x-table.td>
							@if($agent->last_activity_at)
								{{ $agent->last_activity_at->diffForHumans() }}
							@else
								Never
							@endif
						</x-table.td>
						<x-table.td>
							<div class="flex gap-2">
								<a href="{{ route('admin.ai-agents.show', $agent->id) }}" class="text-blue-600 hover:text-blue-800">
									View
								</a>
								<a href="{{ route('admin.ai-agents.edit', $agent->id) }}" class="text-gray-600 hover:text-gray-800">
									Edit
								</a>
							</div>
						</x-table.td>
					</x-table.tr>
				@endforeach
			@else
				<x-table.empty colspan="7"></x-table.empty>
			@endif
		</x-table.tbody>
	</x-table.table>

	@unless($agents->isEmpty())
		<div class="mt-4">
			{{ $agents->onEachSide(1)->withQueryString()->links('pagination.index') }}
		</div>
	@endunless
@endsection
