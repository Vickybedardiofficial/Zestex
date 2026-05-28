<tr class="border-b border-b-bord-tr last:border-none">
	<td class="py-3 px-4 align-top">
		<div class="text-par-m font-semibold text-lab-pr2">
			{{ $accountData->name ?: 'N/A' }}
		</div>
		<div class="text-par-n text-lab-sc">
			{{ $accountData->business_email ?: 'N/A' }}
		</div>
	</td>
	<td class="py-3 px-4 align-top">
		<div class="text-par-m font-semibold text-lab-pr2">
			{{ $accountData->user?->name ?? 'N/A' }}
		</div>
		<div class="text-par-n text-lab-sc">
			{{ '@' . ($accountData->user?->username ?? '') }} {{ $accountData->user?->email ? '· ' . $accountData->user->email : '' }}
		</div>
	</td>
	<td class="py-3 px-4 align-top">
		<div class="text-par-n text-lab-pr2">
			{{ $accountData->country ?: '' }} {{ $accountData->city ? ', ' . $accountData->city : '' }}
		</div>
	</td>
	<td class="py-3 px-4 align-top">
		<div class="text-par-n text-lab-pr2">
			{{ $accountData->updated_at?->getIso() ?? 'N/A' }}
		</div>
	</td>
	<td class="py-3 px-4 align-top">
		@php
			$statusLabel = 'Pending';
			$statusClass = 'text-lab-tr';

			if ($accountData->is_reviewed && $accountData->verified) {
				$statusLabel = 'Approved';
				$statusClass = 'text-green-900';
			}
			else if ($accountData->is_reviewed && (! $accountData->verified)) {
				$statusLabel = 'Rejected';
				$statusClass = 'text-red-900';
			}
		@endphp
		<span class="text-par-n font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
	</td>
	<td class="py-3 px-4 align-top">
		<div class="flex items-center gap-2">
			<form method="POST" action="{{ route('admin.business.accounts.approve', ['accountId' => $accountData->id]) }}">
				@csrf
				<x-ui.buttons.pill type="submit" btnText="Approve"></x-ui.buttons.pill>
			</form>

			<form method="POST" action="{{ route('admin.business.accounts.reject', ['accountId' => $accountData->id]) }}">
				@csrf
				<x-ui.buttons.pill type="submit" btnText="Reject"></x-ui.buttons.pill>
			</form>
		</div>
	</td>
</tr>

