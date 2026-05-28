<footer class="pb-5 pt-8 flex" style="min-width: 320px;">
    <div class="app-container mx-auto flex-1 px-4 md:px-8 max-w-6xl">
        <div class="rounded-2xl border border-fill-qt/70 bg-bg-pr/55 backdrop-blur-md p-4 md:p-5">
            <nav class="flex flex-wrap gap-2.5 md:gap-3">
                <a href="{{ route('document.about.index') }}" class="inline-flex items-center h-8 px-3 rounded-full text-par-s text-lab-pr2 border border-fill-qt/70 hover:text-brand-900 hover:border-brand-900/40 smoothing">
                    {{ __('links.about_project') }}
                </a>
                <a href="{{ route('document.help.index') }}" class="inline-flex items-center h-8 px-3 rounded-full text-par-s text-lab-pr2 border border-fill-qt/70 hover:text-brand-900 hover:border-brand-900/40 smoothing">
                    {{ __('links.help_center') }}
                </a>
                <a href="{{ route('document.terms.index') }}" class="inline-flex items-center h-8 px-3 rounded-full text-par-s text-lab-pr2 border border-fill-qt/70 hover:text-brand-900 hover:border-brand-900/40 smoothing">
                    {{ __('links.terms_of_use') }}
                </a>
                <a href="{{ route('document.privacy.index') }}" class="inline-flex items-center h-8 px-3 rounded-full text-par-s text-lab-pr2 border border-fill-qt/70 hover:text-brand-900 hover:border-brand-900/40 smoothing">
                    {{ __('links.privacy_policy') }}
                </a>
                <a href="{{ route('document.cookies.index') }}" class="inline-flex items-center h-8 px-3 rounded-full text-par-s text-lab-pr2 border border-fill-qt/70 hover:text-brand-900 hover:border-brand-900/40 smoothing">
                    {{ __('links.cookies_policy') }}
                </a>
                <a href="{{ route('document.developers.index') }}" class="inline-flex items-center h-8 px-3 rounded-full text-par-s text-lab-pr2 border border-fill-qt/70 hover:text-brand-900 hover:border-brand-900/40 smoothing">
                    {{ __('links.developers') }}
                </a>

                @if(theme_name() == 'dark')
                    <a href="{{ route('user.theme.switch', ['theme' => 'light']) }}" class="inline-flex items-center h-8 px-3 rounded-full text-par-s text-lab-pr2 border border-fill-qt/70 hover:text-brand-900 hover:border-brand-900/40 smoothing">
                        {{ __('labels.light_theme') }}
                    </a>
                @else
                    <a href="{{ route('user.theme.switch', ['theme' => 'dark']) }}" class="inline-flex items-center h-8 px-3 rounded-full text-par-s text-lab-pr2 border border-fill-qt/70 hover:text-brand-900 hover:border-brand-900/40 smoothing">
                        {{ __('labels.dark_theme') }}
                    </a>
                @endif
            </nav>

            <div class="h-px bg-fill-pr my-4"></div>

            <div class="block md:flex flex-wrap gap-4 items-center">
                <span class="text-par-s text-lab-pr2">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </span>
            </div>
        </div>
    </div>
</footer>
