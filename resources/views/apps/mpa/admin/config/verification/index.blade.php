@extends('adminLayout::index')

@section('pageContent')
	<div class="mb-4">
		<x-page-title titleText=" {{ __('admin/config.verification_settings') }}"></x-page-title>
		<x-page-desc>
			{{ __('admin/config.verification_settings_desc') }}
		</x-page-desc>
	</div>

	<x-sided-content>
		<x-slot:sideContent>
			<x-config.readonly-notice></x-config.readonly-notice>
		</x-slot:sideContent>
		
        <div class="flex flex-col gap-6">
            <form action="{{ route('admin.config.verification.update') }}" method="POST">
                @csrf
                <div class="p-6 bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 flex flex-col gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">
                            {{ __('admin/sidebar.verification') }}
                        </h3>
                        <p class="text-par-s text-lab-sc">
                            {{ __('admin/config.verification_settings_desc') }}
                        </p>
                    </div>

                    <x-form.select
                        name="user_email_verification"
                        labelText="Require Email Verification"
                        :options="[
                            ['key' => 'true', 'value' => 'Enabled (Default)'],
                            ['key' => 'false', 'value' => 'Disabled (Instant Access)']
                        ]"
                        defaultValue="{{ $verificationRequired }}"
                    ></x-form.select>

                    <div class="flex justify-end border-t border-gray-100 dark:border-zinc-800 pt-4 mt-2">
                        <x-auth.buttons.primary type="submit" class="w-auto px-8">Save Changes</x-auth.buttons.primary>
                    </div>
                </div>
            </form>

            <x-config.env
                name="VERIFICATION_SERVICE_URL"
                description="{{ __('admin/config.captions.verification_service_url') }}"
            value="{{ config('verification.service_url') }}"/>
        </div>
	</x-sided-content>
@endsection