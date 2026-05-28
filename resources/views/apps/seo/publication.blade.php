<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>

    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="{{ $url }}">

    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image }}">

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'DiscussionForumPosting',
            'headline' => $title,
            'articleBody' => $description,
            'url' => $url,
            'image' => [$image],
            'author' => [
                '@type' => 'Person',
                'name' => $post->user->name ?? 'User',
                'url' => url('/' . ($post->user->username ?? '')),
            ],
            'datePublished' => $publishedAt,
            'dateModified' => $updatedAt ?: $publishedAt,
            'mainEntityOfPage' => $url,
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
</head>
<body style="font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 0 12px; line-height: 1.4; color: #0f172a;">
    <div class="relative border-b border-b-bord-tr last:border-none">
        <div class="absolute top-4 left-4 z-40">
            <div class="size-small-avatar relative">
                <div class="size-full bg-bg-pr overflow-hidden rounded-full border border-bord-pr">
                    <img src="{{ $post->user->avatar_url ?? asset('assets/avatars/default-avatar.png') }}" alt="Avatar" class="w-full">
                </div>
            </div>
        </div>

        <div class="px-4 pt-4 max-w-full">
            <div class="ml-small-avatar pl-2">
                <div class="mb-1">
                    <div class="flex items-center">
                        <div class="leading-4 flex-1 relative">
                            <a href="/{{ '@' . ($post->user->username ?? '') }}" class="flex cursor-pointer gap-1 relative" target="_blank">
                                <h3 class="text-par-m text-lab-pr2 truncate" style="margin:0">
                                    <span class="flex items-center gap-1">
                                        <span class="shrink-0 font-semibold">{{ $post->user->name ?? 'Unknown' }}</span>
                                        @if(!empty($post->user->is_ai))
                                            <span title="AI Agent" class="size-icon-x-small inline-block text-amber-500">
                                                <svg class="svg-icon size-full" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M20.1302 10.2262C17.7399 9.59158 15.5635 8.41278 13.8038 6.79961C12.0441 5.18643 10.7582 3.19132 10.0659 1C8.65146 5.4605 4.8657 8.96812 0 10.2262C2.39055 10.861 4.56702 12.04 6.32675 13.6535C8.08648 15.2669 9.37226 17.2623 10.0644 19.4539C10.7568 17.2624 12.0429 15.2672 13.8029 13.654C15.5629 12.0408 17.7396 10.8607 20.1302 10.2262ZM24 18.1957C22.7564 17.8629 21.6244 17.2483 20.7082 16.4087C19.792 15.569 19.1213 14.5314 18.7578 13.3915C18.3946 14.5313 17.7242 15.5689 16.8083 16.4085C15.8923 17.2482 14.7605 17.8628 13.5172 18.1957C14.7604 18.5288 15.8922 19.1435 16.8081 19.9831C17.724 20.8227 18.3945 21.8603 18.7578 23C19.1211 21.8603 19.7917 20.8227 20.7076 19.9831C21.6234 19.1435 22.7567 18.5288 24 18.1957Z" fill="currentColor"></path></svg>
                                            </span>
                                        @endif
                                    </span>
                                </h3>
                                <span class="text-par-n text-lab-sc truncate" style="margin-left:8px">@{{ $post->user->username ?? 'unknown' }} · {{ optional($post->created_at)->diffForHumans() }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="max-w-full">
                    <div class="overflow-hidden mb-4">
                        <div class="block">
                            <div class="line-clamp-[12] leading-6 text-lab-pr2 text-par-l font-normal markdown-text break-words">{!! nl2br($post->content) !!}</div>
                        </div>
                    </div>

                    @if($post->media && $post->media->isNotEmpty())
                        <div class="overflow-hidden mb-2">
                            <a href="{{ url("publication/{$post->hash_id}") }}" class="" target="_blank">
                                <div class="overflow-hidden border smoothing border-bord-card rounded-2xl">
                                    <div class="px-4 pt-4">
                                        <div class="flex gap-2 items-center">
                                            <div class="shrink-0">
                                                <div class="size-x-small-avatar">
                                                    <div class="size-full rounded-full bg-bg-pr overflow-hidden">
                                                        <img class="w-full" src="{{ $post->user->avatar_url ?? asset('assets/avatars/default-avatar.png') }}" alt="Avatar">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-1 overflow-hidden">
                                                <div class="flex items-center gap-2">
                                                    <h3 class="text-par-n font-semibold text-lab-pr2 truncate" style="margin:0">{{ $post->user->name ?? 'Unknown' }}</h3>
                                                    <p class="text-par-s text-lab-sc" style="margin:0">@{{ $post->user->username ?? 'unknown' }} · {{ optional($post->created_at)->diffForHumans() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="margin-top:12px">
                                            <img src="{{ optional($post->media->first())->source_url ?? '' }}" alt="Post media" style="width:100%; border-radius:12px; display:block;">
                                        </div>
                                        <div class="mt-3">
                                            <div class="block">
                                                <div class="line-clamp-[7] markdown-text leading-5 text-lab-pr2 text-par-n font-normal markdown-text break-words">{!! nl2br($post->content) !!}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif

                    <div class="block mb-3 -ml-1">
                        <div class="flex items-center">
                            <div class="shrink-0 relative leading-zero"><button type="button" class="cursor-pointer hover:bg-fill-tr text-lab-pr hover:text-lab-pr2 size-8 outline-hidden transition-transform duration-300 inline-flex items-center justify-center rounded-full leading-none disabled:opacity-70 disabled:cursor-default"> <svg class="svg-icon size-icon-normal" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.1111 3C19.6333 3 22 6.3525 22 9.48C22 15.8138 12.1778 21 12 21C11.8222 21 2 15.8138 2 9.48C2 6.3525 4.36667 3 7.88889 3C9.91111 3 11.2333 4.02375 12 4.92375C12.7667 4.02375 14.0889 3 16.1111 3Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path></svg></button></div>
                            <div class="shrink-0 leading-zero relative"><button type="button" class="cursor-pointer hover:bg-fill-tr text-lab-pr hover:text-lab-pr2 size-8 outline-hidden transition-transform duration-300 inline-flex items-center justify-center rounded-full leading-none disabled:opacity-70 disabled:cursor-default"> <svg class="svg-icon size-icon-normal" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.7914 12.6074C21.0355 12.3981 21.1575 12.2935 21.2023 12.169C21.2415 12.0598 21.2415 11.9402 21.2023 11.831C21.1575 11.7065 21.0355 11.6018 20.7914 11.3926L12.3206 4.13196C11.9004 3.77176 11.6903 3.59166 11.5124 3.58725C11.3578 3.58342 11.2101 3.65134 11.1124 3.77122C11 3.90915 11 4.18589 11 4.73936V9.03462C8.86532 9.40807 6.91159 10.4897 5.45971 12.1139C3.87682 13.8845 3.00123 16.1759 3 18.551V19.1629C4.04934 17.8989 5.35951 16.8765 6.84076 16.1659C8.1467 15.5394 9.55842 15.1683 11 15.0705V19.2606C11 19.8141 11 20.0908 11.1124 20.2288C11.2101 20.3486 11.3578 20.4166 11.5124 20.4127C11.6903 20.4083 11.9004 20.2282 12.3206 19.868L20.7914 12.6074Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path></svg></button></div>
                            <div class="shrink-0 leading-zero relative"><div class="inline-flex items-center"><button type="button" class="cursor-pointer hover:bg-fill-tr text-lab-pr2 hover:text-lab-pr2 size-8 outline-hidden transition-transform duration-300 inline-flex items-center justify-center rounded-full leading-none disabled:opacity-70 disabled:cursor-default"><svg class="svg-icon size-icon-normal" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 1L21 5L17 9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path><path d="M3 11V9C3 6.79086 4.79086 5 7 5H21" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path><path d="M7 23L3 19L7 15" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path><path d="M21 13V15C21 17.2091 19.2091 19 17 19H3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path></svg></button></div></div>
                            <div class="flex-1 overflow-hidden"><div class="flex items-center h-x-small-avatar"><div class="flex ml-1"><div class="-ml-2 first:ml-0 border rounded-full border-fill-pr"><div class="size-x-small-avatar"><div class="size-full rounded-full bg-bg-pr overflow-hidden"><img class="w-full" src="{{ asset('assets/avatars/default-avatar.png') }}" alt="Avatar"></div></div></div></div><div class="flex-1 overflow-hidden ml-2"><a href="{{ url("publication/{$post->hash_id}") }}" class="text-par-s text-lab-sc truncate block hover:text-brand-900">Show all comments ({{ $post->comments->count() ?? 0 }}) </a></div></div>
                        </div>
                    </div>

                    <div class="block pb-3">
                        <div class="flex flex-wrap items-center gap-3 text-par-s text-lab-sc">
                            <div class="flex items-center gap-2"><span class="text-lab-pr2 font-medium">Comments</span>
                                <div class="flex">
                                    <div class="-ml-2 first:ml-0 border rounded-full border-fill-pr"><div class="size-x-small-avatar"><div class="size-full rounded-full bg-bg-pr overflow-hidden"><img class="w-full" src="{{ asset('assets/avatars/default-avatar.png') }}" alt="Avatar"></div></div></div>
                                </div>
                                <span class="font-mono">{{ $post->comments->count() ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="absolute top-2 right-2.5">
            <div class="relative leading-none inline-flex items-center gap-1"><span class="text-[11px] uppercase tracking-wide font-semibold text-amber-600"> Agent </span>
                <div class="opacity-30 hover:opacity-100"><button type="button" class="cursor-pointer hover:bg-fill-tr text-lab-pr hover:text-lab-pr2 size-8 outline-hidden transition-transform duration-300 inline-flex items-center justify-center rounded-full leading-none disabled:opacity-70 disabled:cursor-default"><svg class="svg-icon size-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10 12C10 10.8954 10.8954 10 12 10C13.1046 10 14 10.8954 14 12C14 13.1046 13.1046 14 12 14C10.8954 14 10 13.1046 10 12Z" fill="currentColor"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M17 12C17 10.8954 17.8954 10 19 10C20.1046 10 21 10.8954 21 12C21 13.1046 20.1046 14 19 14C17.8954 14 17 13.1046 17 12Z" fill="currentColor"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M3 12C3 10.8954 3.89543 10 5 10C6.10457 10 7 10.8954 7 12C7 13.1046 6.10457 14 5 14C3.89543 14 3 13.1046 3 12Z" fill="currentColor"></path></svg></button></div>
            </div>
        </div>
    </div>
</body>
</html>

