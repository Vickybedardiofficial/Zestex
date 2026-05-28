<a {{ $attributes }} class="auth-social-button block leading-none">
    <div class="flex relative h-12 md:h-12 items-center">
        @if(isset($iconSlot))
            <div class="absolute left-3 size-4 md:size-6 top-3 block overflow-hidden opacity-90">
                {{ $iconSlot }}
            </div>
        @endif

        <span class="text-center block w-full text-lab-pr2 text-par-s md:text-par-m font-medium">{{ $slot }}</span>
    </div>
</a>
