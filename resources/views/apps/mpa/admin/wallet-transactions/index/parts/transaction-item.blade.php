<x-table.tr>
    <x-table.td variant="strong" weight="medium">
        <x-table.avatar :avatarSrc="$transactionData->wallet->user->avatar_url" :name="$transactionData->wallet->user->name" :link="route('admin.users.show', $transactionData->wallet->user->id)" />
    </x-table.td>
    <x-table.td variant="muted">
        {{ $transactionData->wallet->wallet_number }}
    </x-table.td>
    <x-table.td variant="strong" weight="medium">
        {{ $transactionData->transaction_type->label() }}
    </x-table.td>
    <x-table.td variant="muted">
        {{ ucfirst($transactionData->direction->value) }}
    </x-table.td>
    <x-table.td variant="money">
        {{ $transactionData->formatted_amount }}
    </x-table.td>
    <x-table.td variant="muted">
        {{ $transactionData->status->label() }}
    </x-table.td>
    <x-table.td variant="muted">
        {{ $transactionData->created_at->getFormatted() }}
    </x-table.td>
    <x-table.td variant="muted" numeric>
        {{ $transactionData->id }}
    </x-table.td>
    <x-table.td>
        <div class="flex justify-end">
            <a href="{{ route('admin.wallet-transactions.show', $transactionData->id) }}">
                <x-ui.buttons.icon iconName="arrow-up-right" iconType="line"></x-ui.buttons.icon>
            </a>
        </div>
    </x-table.td>
</x-table.tr>

