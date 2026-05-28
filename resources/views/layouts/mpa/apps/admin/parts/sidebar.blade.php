<x-sidebar.container>
    <x-sidebar.navbar>
        <div class="mb-12">
            <a href="{{ route('admin.dash.index') }}" class="flex items-center gap-2">
                <img class="h-5" src="{{ $logotypeUrl }}" alt="Image">
                <span class="font-bold text-lab-pr">
                    {{ __('admin/labels.admin_panel') }}
                </span>
            </a>
        </div>
        <x-sidebar.navlist>
            <x-sidebar.navlist-item
                href="{{ route('user.desktop.index') }}"
                iconName="home-smile"
                iconType="line"
                :trailingIcon="true"
                trailingIconName="arrow-up-right"
                trailingIconType="line"
                target="_blank"
            text="{{ __('admin/sidebar.home') }}"/>
            <x-sidebar.navlist-div/>
            <x-sidebar.navlist-item
                href="{{ route('admin.dash.index') }}"
                iconName="grid-01"
                iconType="line"
                :current="route_is('admin.dash.*')"
            text="{{ __('admin/sidebar.dashboard') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.lab.index') }}"
                iconName="thermometer-cold"
                iconType="line"
                :current="route_is('admin.lab.*')"
            text="{{ __('admin/sidebar.lab_tools') }}"/>
                

            <x-sidebar.navlist-item
                href="{{ route('admin.users.index') }}"
                iconName="user-02"
                iconType="line"
                :current="route_is('admin.users.*')"
            text="{{ __('admin/sidebar.users') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.ai-agents.index') }}"
                iconName="cpu-chip-02"
                iconType="line"
                :current="route_is('admin.ai-agents.*')"
            text="{{ __('admin/sidebar.ai_agents') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.config.ai') }}"
                iconName="settings-01"
                iconType="line"
                :current="route_is('admin.config.ai')"
            text="AI Config"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.ai-analytics.index') }}"
                iconName="bar-chart-01"
                iconType="line"
                :current="route_is('admin.ai-analytics.*')"
            text="AI Analytics"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.special-events.index') }}"
                iconName="lightning-01"
                iconType="line"
                :current="route_is('admin.special-events.*')"
            text="Special Events"/>

            <x-sidebar.navlist-div/>

            <x-sidebar.navlist-item
                href="{{ route('admin.posts.index') }}"
                iconName="layout-alt-02"
                iconType="line"
                :current="route_is('admin.posts.*')"
            text="{{ __('admin/sidebar.publications') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.ads.index') }}"
                iconName="announcement-03"
                iconType="line"
                :current="route_is('admin.ads.*')"
            text="{{ __('admin/sidebar.ads_manager') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.stories.index') }}"
                iconName="refresh-cw-04"
                iconType="line"
                :current="route_is('admin.stories.*')"
            text="{{ __('admin/sidebar.stories') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.market.index') }}"
                iconName="shopping-bag-03"
                iconType="line"
                :current="route_is('admin.market.*')"
            text="{{ __('admin/sidebar.marketplace') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.jobs.index') }}"
                iconName="user-right-01"
                iconType="line"
                :current="route_is('admin.jobs.*')"
            text="{{ __('admin/sidebar.jobs') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.business.accounts.index') }}"
                iconName="briefcase-01"
                iconType="line"
                :current="route_is('admin.business.*')"
            text="Business Accounts"/>
            <x-sidebar.navlist-item
                href="{{ route('admin.chats.index') }}"
                iconName="message-chat-circle"
                iconType="line"
                :current="route_is('admin.chats.*')"
            text="{{ __('admin/sidebar.chats_groups') }}"/>

            <x-sidebar.navlist-div/>

            <x-sidebar.navlist-item
                href="{{ route('admin.config.general') }}"
                iconName="settings-01"
                iconType="line"
                :current="route_is('admin.config.general')"
            text="{{ __('admin/sidebar.general_settings') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.config.email') }}"
                iconName="mail-01"
                iconType="line"
                :current="route_is('admin.config.email')"
            text="{{ __('admin/sidebar.email_settings') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.config.notifications') }}"
                iconName="bell-01"
                iconType="line"
                :current="route_is('admin.config.notifications')"
            text="{{ __('admin/sidebar.notifications') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.config.api') }}"
                iconName="code-02"
                iconType="line"
                :current="route_is('admin.config.api')"
            text="{{ __('admin/sidebar.api_settings') }}"/>

            <x-sidebar.navlist-div/>

            <x-sidebar.navlist-item
                href="{{ route('admin.storage.index') }}"
                iconName="cloud-blank-01"
                iconType="line"
                :current="route_is('admin.storage.*')"
            text="{{ __('admin/sidebar.file_storage') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.coming.index') }}"
                iconName="database-01"
                iconType="line"
                :current="false"
            text="{{ __('admin/sidebar.db_backup') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.payments.index') }}"
                iconName="credit-card-02"
                iconType="line"
                :current="route_is('admin.payments.*')"
            text="{{ __('admin/sidebar.payments') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.categories.index') }}"
                iconName="tag-03"
                iconType="line"
                :current="route_is('admin.categories.*')"
            text="{{ __('admin/sidebar.categories') }}"/>

            <x-sidebar.navlist-div/>

            <x-sidebar.navlist-item
                href="{{ route('admin.reports.index') }}"
                iconName="flag-02"
                iconType="line"
                :current="route_is('admin.reports.*')"
            text="{{ __('admin/sidebar.reported_content') }}"/>
            
            <x-sidebar.navlist-div/>

            <x-sidebar.navlist-item
                href="{{ route('admin.lang.index') }}"
                iconName="translate-01"
                iconType="line"
                :current="route_is('admin.lang.*')"
            text="{{ __('admin/sidebar.languages') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.currency.index') }}"
                iconName="currency-euro"
                iconType="line"
                :current="route_is('admin.currency.*')"
            text="{{ __('admin/sidebar.currency') }}"/>

            <x-sidebar.navlist-div/>

            <x-sidebar.navlist-item
                href="{{ route('admin.config.verification') }}"
                iconName="check-verified-02"
                iconType="line"
                :current="route_is('admin.config.verification')"
            text="{{ __('admin/sidebar.verification') }}"/>
            
            <x-sidebar.navlist-item
                href="{{ route('admin.authorship.index') }}"
                iconName="star-04"
                iconType="line"
                :current="route_is('admin.authorship.*')"
            text="{{ __('admin/sidebar.authorship') }}"/>

            <x-sidebar.navlist-item
                href="{{ route('admin.wallet-transactions.index') }}"
                iconName="credit-card-up"
                iconType="line"
                :current="route_is('admin.wallet-transactions.*')"
            text="Wallet Transactions"/>

            <x-sidebar.navlist-div/>

            <x-sidebar.navlist-item
                href="{{ route('admin.pages.index') }}"
                iconName="file-02"
                iconType="line"
                :current="route_is('admin.pages.*')"
            text="{{ __('admin/sidebar.static_pages') }}"/>

            <x-sidebar.navlist-div/>

            <x-sidebar.navlist-item
                href="{{ route('admin.banning.index') }}"
                iconName="slash-octagon"
                iconType="line"
                :current="route_is('admin.banning.*')"
            text="{{ __('admin/sidebar.banned') }}"/>

            <x-sidebar.navlist-div/>

            <x-sidebar.navlist-item
                href="{{ url(config('log-viewer.route_path')) }}"
                iconName="alert-triangle"
                iconType="line"
            text="{{ __('admin/sidebar.logging') }}"/>

        </x-sidebar.navlist>
		<div class="mt-auto py-6">
			<div class="flex flex-wrap gap-1">
                <x-sidebar.link href="{{ url('settings/theme') }}" target="_blank">
                    {{ __('labels.theme') }}
                </x-sidebar.link>
                <x-sidebar.link href="{{ route('document.help.index') }}" target="_blank">
                    {{ __('business/labels.help') }}
                </x-sidebar.link>
                
                <x-sidebar.link href="{{ route('document.developers.index') }}" target="_blank">
                    {{ __('business/labels.for_developers') }}
                </x-sidebar.link>
                <x-sidebar.link href="{{ route('document.privacy.index') }}" target="_blank">
                    {{ __('business/labels.privacy_policy') }}
                </x-sidebar.link>
                <x-sidebar.link href="{{ route('document.terms.index') }}" target="_blank">
                    {{ __('business/labels.terms_of_use') }}
                </x-sidebar.link>
                <x-sidebar.link href="{{ url('settings/language') }}" target="_blank">
                    {{ __('business/labels.language') }}
                </x-sidebar.link>
                
                <x-sidebar.link href="{{ url('/') }}" target="_blank">
                    {{ config('app.name') }} &copy; {{ now()->year }}  Version #{{ $appVersion }}
                </x-sidebar.link>
                <x-sidebar.link href="{{ route('document.cookies.index') }}" target="_blank">
                    {{ __('links.cookies_policy') }}
                </x-sidebar.link>

                @unless(config('app.hide_author_attribution'))
                    <x-sidebar.link href="https://" target="_blank">Created by Vicky Bedardi Yadav</x-sidebar.link>
                @endunless
			</div>
		</div>
    </x-sidebar.navbar>
</x-sidebar.container>
