@extends('businessLayout::index')

@section('pageContent')
	<div class="mb-6">
		<x-page-title titleText="{{ __('business/settings.index_title') }}"></x-page-title>
	</div>
	<div class="block bg-bg-pr rounded-2xl p-6">
		<div class="mb-3">
			<div class="mb-24">
				<h5 class="text-par-l font-semibold text-lab-pr2 mb-1">
					{{ __('business/settings.business_account') }}

					@if($accountData && $accountData->updated_at)
						@if(empty($accountData->is_reviewed))
							<span class="text-lab-tr">
								({{ __('business/settings.reviewing') }})
							</span>
						@elseif($accountData->verified)
							<span class="text-green-900">
								({{ __('business/settings.verified') }}) &check;
							</span>
						@else
							<span class="text-red-900">
								({{ __('business/settings.rejected') }})
							</span>
						@endif
					@endif
				</h5>
				@if($accountData && $accountData->updated_at)
					<p class="text-par-n font-normal text-lab-pr2">
						{{ __('business/settings.last_submission', ['date' => $accountData->updated_at->getIso()]) }}
					</p>
				@else
					<p class="text-par-n font-normal text-lab-pr2">
						{{ __('business/settings.no_submission') }}
					</p>
				@endif

                @if($accountData)
                    <div class="mt-6 border border-bord-tr rounded-2xl overflow-hidden">
                        <div class="px-5 py-3 bg-fill-fv">
                            <h6 class="text-par-n font-semibold text-lab-pr2">Business details</h6>
                        </div>
                        <div class="px-5 py-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <div class="text-cap-s text-lab-sc">Name</div>
                                <div class="text-par-n text-lab-pr2">{{ $accountData->name ?: me()->name }}</div>
                            </div>
                            <div>
                                <div class="text-cap-s text-lab-sc">Website</div>
                                <div class="text-par-n text-lab-pr2">{{ $accountData->website ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-cap-s text-lab-sc">Business email</div>
                                <div class="text-par-n text-lab-pr2">{{ $accountData->business_email ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-cap-s text-lab-sc">Business phone</div>
                                <div class="text-par-n text-lab-pr2">{{ $accountData->business_phone ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-cap-s text-lab-sc">Tax number</div>
                                <div class="text-par-n text-lab-pr2">{{ $accountData->tax_number ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-cap-s text-lab-sc">Country</div>
                                <div class="text-par-n text-lab-pr2">{{ $accountData->country ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-cap-s text-lab-sc">City</div>
                                <div class="text-par-n text-lab-pr2">{{ $accountData->city ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-cap-s text-lab-sc">Address</div>
                                <div class="text-par-n text-lab-pr2">
                                    @php
                                        $addr = trim(implode(' ', array_filter([
                                            $accountData->address_line1,
                                            $accountData->address_line2,
                                        ])));
                                    @endphp
                                    {{ $addr !== '' ? $addr : '—' }}
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <div class="text-cap-s text-lab-sc">Description</div>
                                <div class="text-par-n text-lab-pr2">{{ $accountData->description ?: '—' }}</div>
                            </div>
                        </div>
                    </div>
                @endif

				<div class="block mt-4">
					<a href="{{ route('business.settings.edit') }}">
						<x-ui.buttons.pill
							type="button"
						btnText="{{ __('business/settings.edit_account_btn') }}"></x-ui.buttons.pill>
					</a>
				</div>
			</div>

			<h5 class="text-par-n font-semibold text-lab-sc">
				{{ __('business/settings.business_info_policy') }}
			</h5>
			<p class="text-par-n font-normal text-lab-sc">
				{!! __('business/settings.business_info_policy_description', ['url' => route('business.settings.edit')]) !!}
			</p>
		</div>
	</div>
@endsection
