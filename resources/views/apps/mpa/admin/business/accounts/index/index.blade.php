@extends('adminLayout::index')

@section('pageContent')
	<div class="mb-8">
		<x-page-title titleText="Business Accounts Review"></x-page-title>
		<x-page-desc>
			Pending business accounts need admin approval. Use Approve/Reject to change status.
		</x-page-desc>
	</div>

	<div class="mb-4">
		<x-tabs.tabs>
			<x-tabs.tab-item :active="$filters['status'] == 'pending'" href="{{ route('admin.business.accounts.index', ['status' => 'pending']) }}" textLabel="Pending"></x-tabs.tab-item>
			<x-tabs.tab-item :active="$filters['status'] == 'approved'" href="{{ route('admin.business.accounts.index', ['status' => 'approved']) }}" textLabel="Approved"></x-tabs.tab-item>
			<x-tabs.tab-item :active="$filters['status'] == 'rejected'" href="{{ route('admin.business.accounts.index', ['status' => 'rejected']) }}" textLabel="Rejected"></x-tabs.tab-item>
			<x-tabs.tab-item :active="$filters['status'] == 'all'" href="{{ route('admin.business.accounts.index', ['status' => 'all']) }}" textLabel="All"></x-tabs.tab-item>
		</x-tabs.tabs>
	</div>

	<x-table.table>
		<x-slot:filter>
			<div class="mb-4">
				<form action="{{ route('admin.business.accounts.index') }}" method="GET">
					<input type="hidden" name="status" value="{{ $filters['status'] }}">
					<x-search.searchbar :value="$filters['search']" :cancelUrl="route('admin.business.accounts.index', ['status' => $filters['status']])" />
					<div class="mt-1">
						<x-search.desc description="Search by business name, business email, user email, username." />
					</div>
				</form>
			</div>
		</x-slot:filter>

		<x-table.thead>
			<x-table.th>Business</x-table.th>
			<x-table.th>User</x-table.th>
			<x-table.th>Location</x-table.th>
			<x-table.th>Updated</x-table.th>
			<x-table.th>Status</x-table.th>
			<x-table.th>Actions</x-table.th>
		</x-table.thead>

		<x-table.tbody>
			@if($accounts->isNotEmpty())
				@foreach ($accounts as $accountData)
					@include('apps.mpa.admin.business.accounts.index.parts.account-item', [
						'accountData' => $accountData
					])
				@endforeach
			@else
				<x-table.empty colspan="6"></x-table.empty>
			@endif
		</x-table.tbody>
	</x-table.table>

	@unless($accounts->isEmpty())
		<div class="mt-4">
			{{ $accounts->onEachSide(1)->withQueryString()->links('pagination.index') }}
		</div>
	@endunless
@endsection

