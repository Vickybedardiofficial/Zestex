@props([
    'type' => 'solid',
    'name' => '',
    'fill' => false
])

@php
    $path = "resources/assets/ui-icons/{$type}/{$name}.svg";
    $iconPath = base_path($path);
@endphp

@if (file_exists($iconPath))
    <svg class="size-full {{ $fill ? $fill : '' }}" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        {!! file_get_contents($iconPath) !!}
    </svg>
@else
    <!-- Icon not found: {{ $path }} -->
@endif