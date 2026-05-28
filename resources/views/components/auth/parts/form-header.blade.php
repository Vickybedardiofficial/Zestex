@props([
    'title' => ''
])

<div class="block">
    @if (isset($icon))
        <div class="auth-header-icon size-9 overflow-hidden mb-3 p-2 rounded-lg inline-flex items-center justify-center">
            {!! $icon !!}
        </div>
    @endif

    <h1 class="auth-header-title">
        {!! $title !!}
    </h1>

    @if(isset($caption))
        <p class="auth-header-caption mt-1.5">
            {!! $caption !!}
        </p>
    @endif
</div>
