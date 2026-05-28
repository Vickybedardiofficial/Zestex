@extends('adminLayout::index')

@section('pageContent')
    <div class="mb-8">
        <x-page-title titleText="Wallet transactions"></x-page-title>
        <x-page-desc>
            This page shows user wallet deposits, transfers, refunds, withdrawals, amount, status, and date/time.
        </x-page-desc>
    </div>

    <form method="GET" class="mb-5 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <input name="search" value="{{ $filters['search'] }}" placeholder="Search user/wallet/ref" class="h-10 rounded-lg border border-bord-pr px-3 bg-bg-pr text-lab-pr2">
        <select name="type" class="h-10 rounded-lg border border-bord-pr px-2 bg-bg-pr text-lab-pr2">
            <option value="all" @selected($filters['type'] === 'all')>All types</option>
            <option value="deposit" @selected($filters['type'] === 'deposit')>Deposit</option>
            <option value="withdraw" @selected($filters['type'] === 'withdraw')>Withdraw</option>
            <option value="transfer" @selected($filters['type'] === 'transfer')>Transfer</option>
            <option value="payment" @selected($filters['type'] === 'payment')>Payment</option>
            <option value="refund" @selected($filters['type'] === 'refund')>Refund</option>
            <option value="advertising" @selected($filters['type'] === 'advertising')>Advertising</option>
        </select>
        <select name="status" class="h-10 rounded-lg border border-bord-pr px-2 bg-bg-pr text-lab-pr2">
            <option value="all" @selected($filters['status'] === 'all')>All status</option>
            <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
            <option value="completed" @selected($filters['status'] === 'completed')>Completed</option>
            <option value="failed" @selected($filters['status'] === 'failed')>Failed</option>
            <option value="cancelled" @selected($filters['status'] === 'cancelled')>Cancelled</option>
        </select>
        <select name="direction" class="h-10 rounded-lg border border-bord-pr px-2 bg-bg-pr text-lab-pr2">
            <option value="all" @selected($filters['direction'] === 'all')>All direction</option>
            <option value="incoming" @selected($filters['direction'] === 'incoming')>Incoming</option>
            <option value="outgoing" @selected($filters['direction'] === 'outgoing')>Outgoing</option>
        </select>
        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="h-10 rounded-lg border border-bord-pr px-3 bg-bg-pr text-lab-pr2">
        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="h-10 rounded-lg border border-bord-pr px-3 bg-bg-pr text-lab-pr2">

        <div class="md:col-span-3 lg:col-span-6 flex gap-2">
            <button type="submit" class="h-10 px-4 rounded-lg bg-brand-900 text-white text-par-s font-semibold">Apply filters</button>
            <a href="{{ route('admin.wallet-transactions.index') }}" class="h-10 px-4 rounded-lg border border-bord-pr text-par-s text-lab-pr2 inline-flex items-center">Reset</a>
        </div>
    </form>

    <x-table.table>
        <x-table.thead>
            <x-table.th>{{ __('table.labels.user') }}</x-table.th>
            <x-table.th>Wallet</x-table.th>
            <x-table.th>{{ __('table.labels.type') }}</x-table.th>
            <x-table.th>Direction</x-table.th>
            <x-table.th>{{ __('table.labels.amount') }}</x-table.th>
            <x-table.th>{{ __('table.labels.status') }}</x-table.th>
            <x-table.th>{{ __('table.labels.created_at') }}</x-table.th>
            <x-table.th>#ID</x-table.th>
            <x-table.th>{{ __('labels.table.actions') }}</x-table.th>
        </x-table.thead>
        <x-table.tbody>
            @if($transactions->isNotEmpty())
                @foreach ($transactions as $transactionData)
                    @include('admin::wallet-transactions.index.parts.transaction-item', [
                        'transactionData' => $transactionData
                    ])
                @endforeach
            @else
                <x-table.empty colspan="9"></x-table.empty>
            @endif
        </x-table.tbody>
    </x-table.table>

    @unless($transactions->isEmpty())
        <div class="mt-4">
            {{ $transactions->onEachSide(1)->withQueryString()->links('pagination.index') }}
        </div>
    @endunless
@endsection

