@extends('adminLayout::index')

@section('pageContent')
    <div class="mb-4">
        <x-page-title titleText="Wallet transaction details"></x-page-title>
        <x-page-desc>
            Full details of user wallet transaction: who, when, amount, type, status, and metadata.
        </x-page-desc>
    </div>

    <x-sided-content>
        <x-slot:sideContent>
            <x-entity.previews.payment :paymentData="(object) [
                'user' => $transactionData->wallet->user,
                'formatted_amount' => $transactionData->formatted_amount,
                'currency' => $transactionData->currency,
                'status' => $transactionData->status,
                'payment_type' => $transactionData->transaction_type,
                'provider_name' => $transactionData->metadata['source']['name'] ?? 'N/A'
            ]"></x-entity.previews.payment>
        </x-slot:sideContent>

        <div class="mb-4">
            <x-entity.title title="Transaction overview" caption="{{ $transactionData->created_at->getFormatted() }}"></x-entity.title>
        </div>

        <div class="mb-6">
            <x-counter.counter>
                <x-counter.counter-item counterValue="{{ $transactionData->formatted_amount }}" captionText="{{ __('table.labels.amount') }}"></x-counter.counter-item>
                <x-counter.counter-item counterValue="{{ strtoupper($transactionData->currency) }}" captionText="{{ __('labels.currency') }}"></x-counter.counter-item>
                <x-counter.counter-item counterValue="{{ $transactionData->formatted_commission_amount }}" captionText="Commission"></x-counter.counter-item>
            </x-counter.counter>
        </div>

        <div class="mb-6">
            <x-line-table.table>
                <x-line-table.row>
                    <x-slot:labelText>{{ __('table.labels.user') }}</x-slot:labelText>
                    <x-slot:labelValue>
                        <a href="{{ route('admin.users.show', $transactionData->wallet->user->id) }}" target="_blank" class="underline">
                            {{ $transactionData->wallet->user->name }}
                        </a>
                    </x-slot:labelValue>
                </x-line-table.row>
                <x-line-table.row>
                    <x-slot:labelText>Wallet number</x-slot:labelText>
                    <x-slot:labelValue>{{ $transactionData->wallet->wallet_number }}</x-slot:labelValue>
                </x-line-table.row>
                <x-line-table.row>
                    <x-slot:labelText>{{ __('table.labels.type') }}</x-slot:labelText>
                    <x-slot:labelValue>{{ $transactionData->transaction_type->label() }}</x-slot:labelValue>
                </x-line-table.row>
                <x-line-table.row>
                    <x-slot:labelText>Direction</x-slot:labelText>
                    <x-slot:labelValue>{{ ucfirst($transactionData->direction->value) }}</x-slot:labelValue>
                </x-line-table.row>
                <x-line-table.row>
                    <x-slot:labelText>{{ __('table.labels.status') }}</x-slot:labelText>
                    <x-slot:labelValue>{{ $transactionData->status->label() }}</x-slot:labelValue>
                </x-line-table.row>
                <x-line-table.row>
                    <x-slot:labelText>Source</x-slot:labelText>
                    <x-slot:labelValue>{{ $transactionData->metadata['source']['name'] ?? 'N/A' }}</x-slot:labelValue>
                </x-line-table.row>
            </x-line-table.table>
        </div>

        <div class="mb-4">
            <x-entity.title title="{{ __('labels.additional_info') }}"></x-entity.title>
        </div>

        <div class="mb-6">
            <x-striped-table.table>
                <x-striped-table.row>
                    <x-slot:labelText>#ID</x-slot:labelText>
                    <x-slot:labelValue>{{ $transactionData->id }}</x-slot:labelValue>
                </x-striped-table.row>
                <x-striped-table.row>
                    <x-slot:labelText>Transaction ID</x-slot:labelText>
                    <x-slot:labelValue>{{ $transactionData->tnx_id }}</x-slot:labelValue>
                </x-striped-table.row>
                <x-striped-table.row>
                    <x-slot:labelText>{{ __('table.labels.created_at') }}</x-slot:labelText>
                    <x-slot:labelValue>{{ $transactionData->created_at->getFormatted() }}</x-slot:labelValue>
                </x-striped-table.row>
            </x-striped-table.table>
        </div>

        @if(!empty($metadata))
            <div class="mb-4">
                <x-entity.title title="{{ __('labels.metadata') }}"></x-entity.title>
            </div>

            <x-striped-table.table>
                @foreach($metadata as $key => $value)
                    <x-striped-table.row>
                        <x-slot:labelText>{{ strtoupper($key) }}</x-slot:labelText>
                        <x-slot:labelValue>
                            @if(is_array($value))
                                {{ json_encode($value) }}
                            @else
                                {{ $value }}
                            @endif
                        </x-slot:labelValue>
                    </x-striped-table.row>
                @endforeach
            </x-striped-table.table>
        @endif
    </x-sided-content>
@endsection

