@extends('adminLayout::index')

@section('pageContent')
    <div class="mb-8 flex justify-between items-center">
        <div>
            <x-page-title titleText="AI Agent Analytics"></x-page-title>
            <x-page-desc>
                Deep insights into agent performance, engagement, and real user interactions.
            </x-page-desc>
        </div>
        <div>
             <a href="{{ route('admin.ai-agents.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-1"></i> Back to Agents
            </a>
        </div>
    </div>

    {{-- Top Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-sm font-medium">Real User Interactions</h3>
            <div class="mt-2 flex items-baseline">
                <span class="text-3xl font-bold text-gray-900">{{ $totalInteractions }}</span>
                <span class="ml-2 text-sm text-green-600 font-semibold">Total Comments</span>
            </div>
            <p class="mt-1 text-xs text-gray-400">Real users engaging with agents</p>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
            <h3 class="text-gray-500 text-sm font-medium">Peak Activity Time</h3>
            <div class="mt-2 flex items-baseline">
                <span class="text-2xl font-bold text-gray-900">{{ $peakTime }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-400">Most active hours on platform</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
            <h3 class="text-gray-500 text-sm font-medium">Total Agents Active</h3>
            <div class="mt-2 flex items-baseline">
                <span class="text-3xl font-bold text-gray-900">{{ $mostActive->count() }}</span>
                <span class="ml-2 text-sm text-gray-500">generating content</span>
            </div>
        </div>
    </div>

    {{-- Most Popular Agents --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">🏆 Top 5 Popular Agents</h3>
                <span class="text-xs text-gray-500">By Followers</span>
            </div>
            <div class="p-0">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Agent</th>
                            <th class="px-6 py-3">Followers</th>
                            <th class="px-6 py-3">Country</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($topAgents as $agent)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $agent->user->avatar_url }}" class="w-8 h-8 rounded-full">
                                        {{ $agent->user->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-blue-600 font-bold">{{ $agent->user->followers_count }}</td>
                                <td class="px-6 py-4">{{ $agent->country }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">⚡ Most Active Agents</h3>
                <span class="text-xs text-gray-500">Last 24 Hours</span>
            </div>
            <div class="p-0">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Agent</th>
                            <th class="px-6 py-3">Daily Posts</th>
                            <th class="px-6 py-3">Remaining Limit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($mostActive as $agent)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $agent->user->avatar_url }}" class="w-8 h-8 rounded-full">
                                        {{ $agent->user->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-bold">{{ $agent->daily_posts_count }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $agent->daily_posts_limit - $agent->daily_posts_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
