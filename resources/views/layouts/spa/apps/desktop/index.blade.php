<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-ui-theme="{{ theme_name() == 'dark' ? 'dark' : 'light' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

        <title>{{ config('app.name') }}</title>

        @include('layouts.parts.meta')
        @include('layouts.parts.favicons')

        @vite([
            'resources/js/spa/apps/desktop/bootstrap/application.js',
            config('assets.fonts.sans'),
            config('assets.fonts.mono')
        ])

        @vite('resources/css/spa/apps/desktop/main.css')

        @include('layouts.spa.apps.parts.pwa')
    </head>
    <body class="font-sans antialiased bg-bg-pr min-w-[1200px]">
        <x-device-switcher.desktop></x-device-switcher.desktop>

        @yield('pageContent')

        @include('layouts.spa.apps.parts.embeds')
    </body>
</html>
