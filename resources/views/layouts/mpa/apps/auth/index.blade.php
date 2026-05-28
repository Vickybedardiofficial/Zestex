<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

        <title>{{ config('app.name') }}</title>

        @include('layouts.parts.meta')
        @include('layouts.parts.favicons')

        @vite([
            config('assets.fonts.sans'),
            config('assets.fonts.mono')
        ])

        @vite('resources/css/spa/apps/desktop/auth.css')
        
        @livewireStyles
        @php
            $isDarkTheme = theme_name() == 'dark';
            $totalUsers = cache()->remember('auth:hero:total_users:live', 60, function () {
                return \Illuminate\Support\Facades\Schema::hasTable('users')
                    ? (int) \App\Models\User::count()
                    : 0;
            });

            $totalComments = cache()->remember('auth:hero:total_comments:live', 60, function () {
                return \Illuminate\Support\Facades\Schema::hasTable('comments')
                    ? (int) \App\Models\Comment::count()
                    : 0;
            });

            $totalPosts = cache()->remember('auth:hero:total_posts:live', 60, function () {
                return \Illuminate\Support\Facades\Schema::hasTable('posts')
                    ? (int) \App\Models\Post::count()
                    : 0;
            });
        @endphp
        <style>
            :root{
                --auth-shell-bg: {{ $isDarkTheme ? '#0f1115' : '#f8fafc' }};
                --auth-card-bg: {{ $isDarkTheme ? '#161a22' : '#ffffff' }};
                --auth-card-border: {{ $isDarkTheme ? '#2a3240' : '#e2e8f0' }};
                --auth-title: {{ $isDarkTheme ? '#f8fafc' : '#0f172a' }};
                --auth-caption: {{ $isDarkTheme ? '#94a3b8' : '#475569' }};
                --auth-brand: {{ $isDarkTheme ? '#60a5fa' : '#0284c7' }};
                --auth-input-bg: {{ $isDarkTheme ? '#111827' : '#ffffff' }};
                --auth-input-border: {{ $isDarkTheme ? '#334155' : '#d0d5dd' }};
                --auth-input-text: {{ $isDarkTheme ? '#e2e8f0' : '#0f172a' }};
                --auth-input-placeholder: {{ $isDarkTheme ? '#94a3b8' : '#64748b' }};
                --auth-soft-bg: {{ $isDarkTheme ? '#1f2937' : '#f1f5f9' }};
                --auth-social-hover: {{ $isDarkTheme ? '#1e293b' : '#f8fafc' }};
                --auth-hero-bg-start: {{ $isDarkTheme ? '#0b1220' : '#f1f5f9' }};
                --auth-hero-bg-end: {{ $isDarkTheme ? '#111827' : '#e0e8ef' }};
                --auth-hero-border: {{ $isDarkTheme ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.08)' }};
                --auth-hero-title: {{ $isDarkTheme ? '#f8fafc' : '#0f172a' }};
                --auth-hero-text: {{ $isDarkTheme ? '#94a3b8' : '#475569' }};
                --auth-hero-tag-bg: {{ $isDarkTheme ? 'rgba(96,165,250,.16)' : 'rgba(2,132,199,.12)' }};
                --auth-hero-tag-text: {{ $isDarkTheme ? '#bfdbfe' : '#0c4a6e' }};
                --auth-hero-card-bg: {{ $isDarkTheme ? 'rgba(15,23,42,.35)' : 'rgba(226,232,240,.5)' }};
                --auth-hero-card-border: {{ $isDarkTheme ? 'rgba(148,163,184,.24)' : 'rgba(15,23,42,.1)' }};
                --auth-hero-dot: {{ $isDarkTheme ? '#38bdf8' : '#0284c7' }};
            }
            .auth-page-frame{max-width:1120px;margin:0 auto;position:relative}
            .auth-layout-grid{display:grid;grid-template-columns:1fr;gap:1.25rem;align-items:stretch;grid-auto-flow:dense}
            .auth-content{width:100%;max-width:460px;margin:0 auto;padding:0 0.5rem}
            .auth-form-stage{display:flex;align-items:center;justify-content:center;order:1}
            .auth-form-shell{background:var(--auth-card-bg);border:1px solid var(--auth-card-border);border-radius:24px;padding:1.25rem;box-shadow:0 18px 40px rgba(2,6,23,.08);width:100%}
            .auth-form-shell .text-lab-pr2{color:var(--auth-title)!important}
            .auth-form-shell .text-lab-sc{color:var(--auth-caption)!important}
            .auth-form-shell .text-brand-900{color:var(--auth-brand)!important}
            .auth-form-shell .text-par-m,.auth-form-shell .text-par-s{line-height:1.45}
            .auth-form-shell .border-bord-pr{border-color:var(--auth-input-border)!important}
            .auth-form-shell .border-fill-qt\/60{border-color:var(--auth-card-border)!important}
            .auth-form-shell .bg-fill-fv,.auth-form-shell .bg-fill-fv\/40{background:var(--auth-soft-bg)!important}
            .auth-form-shell .bg-input-pr{background:var(--auth-input-bg)!important}
            .auth-form-input{width:100%;height:48px;border-radius:12px;border:1px solid var(--auth-input-border);padding:0 1rem;font-size:15px;line-height:1.2;outline:none;background:var(--auth-input-bg);color:var(--auth-input-text);-webkit-appearance:none;appearance:none}
            .auth-form-input::placeholder{color:var(--auth-input-placeholder);opacity:1}
            .auth-form-input:focus{border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.18)}
            .auth-primary-button{border-radius:12px;width:100%}
            .auth-social-button{border:1px solid var(--auth-input-border);border-radius:12px;background:var(--auth-card-bg);width:100%}
            .auth-social-button .text-lab-pr2{color:var(--auth-title)!important}
            .auth-social-button:hover{background:var(--auth-social-hover)}
            .auth-link{display:block;text-align:center;color:var(--auth-brand);font-weight:600}
            .auth-link:hover{text-decoration:underline}
            .auth-header-icon{color:var(--auth-brand);background:{{ $isDarkTheme ? '#1e3a8a55' : '#e0f2fe' }}}
            .auth-header-title{font-size:1.75rem;line-height:1.2;font-weight:700;color:var(--auth-title);letter-spacing:-0.01em}
            .auth-header-caption{font-size:.98rem;line-height:1.45;color:var(--auth-caption)}
            .auth-deco{position:absolute;border-radius:9999px;filter:blur(42px);opacity:.22;pointer-events:none}
            .auth-deco-one{width:280px;height:280px;left:4%;top:4%;background:{{ $isDarkTheme ? '#1d4ed8' : '#e0f2fe' }}}
            .auth-deco-two{width:240px;height:240px;right:8%;bottom:8%;background:{{ $isDarkTheme ? '#0ea5e9' : '#cffafe' }}}
            .auth-hero-panel{display:block;background:linear-gradient(145deg,var(--auth-hero-bg-start),var(--auth-hero-bg-end));border:1px solid var(--auth-hero-border);border-radius:24px;min-height:auto;order:2}
            .auth-hero-inner{height:100%;display:flex;flex-direction:column;justify-content:space-between;padding:1.5rem}
            .auth-hero-tag{display:inline-flex;align-items:center;height:32px;padding:0 12px;border-radius:999px;background:var(--auth-hero-tag-bg);color:var(--auth-hero-tag-text);font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
            .auth-hero-title{font-size:1.65rem;line-height:1.2;font-weight:700;color:var(--auth-hero-title);margin-top:.9rem}
            .auth-hero-sub{font-size:.95rem;line-height:1.5;color:var(--auth-hero-text);margin-top:.7rem;max-width:26rem}
            .auth-hero-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-top:1rem}
            .auth-hero-card{border:1px solid var(--auth-hero-card-border);border-radius:14px;padding:.85rem;background:var(--auth-hero-card-bg)}
            .auth-hero-card-title{font-size:.73rem;color:var(--auth-hero-text);text-transform:uppercase;letter-spacing:.05em}
            .auth-hero-card-value{font-size:1rem;color:var(--auth-hero-title);font-weight:600;margin-top:.25rem}
            .auth-hero-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem;margin-top:1rem}
            .auth-hero-metric{border:1px solid var(--auth-hero-card-border);border-radius:14px;padding:.85rem;background:var(--auth-hero-card-bg)}
            .auth-hero-metric-title{font-size:.7rem;color:var(--auth-hero-text);text-transform:uppercase;letter-spacing:.05em}
            .auth-hero-metric-value{font-size:1.1rem;color:var(--auth-hero-title);font-weight:700;margin-top:.22rem;word-break:break-word}
            .auth-hero-points{display:grid;gap:.55rem;margin-top:1rem}
            .auth-hero-point{display:flex;align-items:center;gap:.5rem;color:var(--auth-hero-text);font-size:.9rem}
            .auth-hero-dot{width:6px;height:6px;border-radius:999px;background:var(--auth-hero-dot);display:inline-block}
            @media (max-width: 1023px){
                .auth-layout-grid{gap:0.75rem}
                .auth-hero-metrics{grid-template-columns:repeat(2,minmax(0,1fr));gap:.5rem}
                .auth-hero-grid{grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.5rem}
                .auth-hero-card{padding:.5rem;border-radius:10px}
                .auth-hero-card-title{font-size:.65rem}
                .auth-hero-card-value{font-size:.85rem;margin-top:.1rem}
                .auth-hero-metric{padding:.5rem;border-radius:10px}
                .auth-hero-metric-title{font-size:.65rem}
                .auth-hero-metric-value{font-size:.85rem;margin-top:.1rem;word-break:break-word}
                .auth-hero-title{font-size:1.25rem;margin-top:.5rem}
                .auth-hero-sub{font-size:.85rem;margin-top:.3rem}
                .auth-hero-tag{height:28px;padding:0 10px;font-size:10px}
                .auth-hero-inner{padding:.85rem}
                .auth-hero-points{gap:.35rem;margin-top:.5rem;display:none}
            }
            @media (min-width:1024px){
                .auth-layout-grid{grid-template-columns:1.08fr .92fr;gap:2rem;grid-auto-flow:row}
                .auth-hero-panel{min-height:560px;order:0}
                .auth-form-stage{order:0}
                .auth-form-shell{padding:1.75rem}
                .auth-header-title{font-size:2rem}
            }
        </style>
        @stack('styles')
    </head>
    <body class="pt-28 md:pt-28 auth-shell" style="min-width: 320px; background-color: var(--auth-shell-bg);">
        @include('layouts.mpa.parts.header')

        <div class="flex-col flex min-h-screen auth-page-root">
            <div class="relative flex-1 px-3 py-6 md:px-4 md:py-8 lg:py-12">
                <div class="auth-deco auth-deco-one"></div>
                <div class="auth-deco auth-deco-two"></div>

                <div class="auth-page-frame">
                    <div class="auth-layout-grid">
                        <section class="auth-hero-panel" aria-hidden="true">
                            <div class="auth-hero-inner">
                                <div>
                                    <span class="auth-hero-tag">Security First</span>
                                    <h2 class="auth-hero-title">Welcome to {{ config('app.name') }} authentication</h2>
                                    <p class="auth-hero-sub">Secure login, signup, and recovery designed for fast access and clean experience on desktop and mobile.</p>

                                    <div class="auth-hero-grid">
                                        <div class="auth-hero-card">
                                            <div class="auth-hero-card-title">Authentication</div>
                                            <div class="auth-hero-card-value">Email + Social</div>
                                        </div>
                                        <div class="auth-hero-card">
                                            <div class="auth-hero-card-title">Recovery</div>
                                            <div class="auth-hero-card-value">Token Protected</div>
                                        </div>
                                    </div>

                                    <div class="auth-hero-metrics">
                                        <div class="auth-hero-metric">
                                            <div class="auth-hero-metric-title">Total Users</div>
                                            <div class="auth-hero-metric-value">{{ number_format($totalUsers) }}</div>
                                        </div>
                                        <div class="auth-hero-metric">
                                            <div class="auth-hero-metric-title">Total Comments</div>
                                            <div class="auth-hero-metric-value">{{ number_format($totalComments) }}</div>
                                        </div>
                                        <div class="auth-hero-metric">
                                            <div class="auth-hero-metric-title">Total Posts</div>
                                            <div class="auth-hero-metric-value">{{ number_format($totalPosts) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="auth-hero-points">
                                    <div class="auth-hero-point"><span class="auth-hero-dot"></span> Privacy and policy controls available in footer links</div>
                                    <div class="auth-hero-point"><span class="auth-hero-dot"></span> Language switch supported directly from header menu</div>
                                    <div class="auth-hero-point"><span class="auth-hero-dot"></span> Theme switcher available with one click</div>
                                </div>
                            </div>
                        </section>

                        <section class="auth-form-stage">
                            <div class="auth-content">
                                @yield('pageContent')
                            </div>
                        </section>
                    </div>
                </div>
            </div>
            @include('layouts.mpa.parts.footer')
        </div>

        @stack('scripts')
        @livewireScripts
    </body>
</html>
