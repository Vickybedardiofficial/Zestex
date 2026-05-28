<header class="fixed top-0 left-0 right-0 z-50">
    <div class="h-24 md:h-28 flex justify-center md:justify-between px-4 md:px-8 items-center relative pt-6 md:pt-8">
        <div class="hidden md:flex flex-col leading-tight" style="visibility: hidden;">
        </div>

        <a class="inline-flex items-center justify-center px-3 py-2 rounded-xl z-10" href="{{ route('user.desktop.index') }}">
            <img class="h-10 md:h-12" src="{{ $logotypeUrl }}" alt="Logo">
        </a>

        <div class="hidden md:inline-flex gap-3 text-lab-pr font-medium items-center ml-auto relative z-20" style="color: var(--auth-title);">
            <div x-data="{ isOpen: false }" x-on:click.away="isOpen = false" class="relative" x-cloak>
                <button type="button" class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg transition cursor-pointer leading-none" style="border: 1px solid var(--auth-input-border); background-color: var(--auth-card-bg); color: var(--auth-title);" x-on:click.prevent.stop="isOpen = !isOpen">
                    <span class="inline text-par-s font-semibold max-w-28 truncate">
                        {{ $appLanguages->getLocaleName() }}
                    </span>
                    <span class="inline-block size-icon-small">
                        <x-ui-icon name="translate-01" type="line"></x-ui-icon>
                    </span>
                    <span class="size-4 shrink-0 inline-block">
                        <x-ui-icon name="chevron-down"></x-ui-icon>
                    </span>
                </button>
                <div x-show="isOpen" x-transition.origin.top.right class="absolute top-full mt-2 right-0 rounded-xl overflow-hidden min-w-60 ease-in-out transition-all shadow-2xl z-40" style="border: 1px solid var(--auth-input-border); background-color: var(--auth-card-bg);">
                    <div class="block divide-y" style="background-color: var(--auth-card-bg); border-color: var(--auth-input-border);">
                        @foreach ($appLanguages->getLanguages() as $langData)
                            <a href="{{ route('user.language.switch', ['lang' => $langData->alpha_2_code]) }}" rel="nofollow" title="{{ $langData->name }}" class="block px-4 py-2.5 hover:bg-fill-qt smoothing text-lab-pr2 text-par-s {{ empty($langData->current) ? '' : 'bg-fill-qt' }}">
                                {{ $langData->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
