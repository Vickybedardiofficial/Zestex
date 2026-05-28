<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicSeoController extends Controller
{
    public function publication(Request $request, string $hashId)
    {
        // If not a bot/SEO request, return the SPA shell (guest-friendly).
        if (! $this->shouldRenderSeoPage($request)) {
            $deviceType = \Illuminate\Support\Facades\Cookie::get('device_type', 'desktop');
            if ($deviceType === 'mobile') {
                return response()->view('mobile::index');
            }

            return response()->view('desktop::index');
        }

        $post = Post::active()
            ->whereHashId($hashId)
            ->with([
                'user:id,username,first_name,last_name,avatar',
                // Use real media columns; source_url is an accessor, not a database column.
                'media:id,mediaable_id,mediaable_type,source_path,disk,type,status,thumbnail_path,thumbnail_disk',
                'linkSnapshot'
            ])
            ->firstOrFail();

        // For public access allow rendering the SEO page for everyone so
        // publication URLs open directly without requiring SPA bootstrap/auth.
        // Bots will still get the same HTML for scraping.

        $title = $this->buildTitle($post);
        $description = $this->buildDescription($post);
        $image = $this->resolveImage($post);
        $url = url("publication/{$hashId}");

        return response()->view('apps.seo.publication', [
            'post' => $post,
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'url' => $url,
            'publishedAt' => $this->toIso8601($post->created_at),
            'updatedAt' => $this->toIso8601($post->updated_at),
        ]);
    }

    public function sitemap(Request $request)
    {
        $limit = max(100, min(50000, (int) $request->query('limit', 50000)));

        $posts = Post::active()
            ->latest('updated_at')
            ->limit($limit)
            ->get(['id', 'updated_at']);

        $items = $posts->map(function (Post $post) {
            return [
                'loc' => url("publication/{$post->hash_id}"),
                'lastmod' => $this->toAtom($post->updated_at),
            ];
        });

        return response()
            ->view('apps.seo.sitemap', ['items' => $items])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function buildTitle(Post $post): string
    {
        $content = trim((string) $post->content);
        if ($content === '') {
            return "{$post->user->name} posted on " . config('app.name');
        }

        return Str::limit(Str::of($content)->replaceMatches('/\s+/', ' ')->toString(), 70, '');
    }

    private function buildDescription(Post $post): string
    {
        $content = strip_tags((string) $post->content);
        $clean = Str::of($content)->replaceMatches('/\s+/', ' ')->trim()->toString();

        if ($clean === '') {
            return 'Read this post on ' . config('app.name');
        }

        return Str::limit($clean, 160, '...');
    }

    private function resolveImage(Post $post): string
    {
        $mediaUrl = optional($post->media->first())->source_url;
        if ($mediaUrl) {
            return $mediaUrl;
        }

        $avatar = optional($post->user)->avatar_url;
        if ($avatar) {
            return $avatar;
        }

        return asset('favicon.png');
    }

    private function toIso8601(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (method_exists($value, 'toIso8601String')) {
            return $value->toIso8601String();
        }

        try {
            return Carbon::parse((string) $value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }

    private function toAtom(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (method_exists($value, 'toAtomString')) {
            return $value->toAtomString();
        }

        try {
            return Carbon::parse((string) $value)->toAtomString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function shouldRenderSeoPage(Request $request): bool
    {
        if ($request->boolean('seo')) {
            return true;
        }

        $ua = mb_strtolower((string) $request->userAgent());
        $botSignatures = [
            'bot',
            'crawler',
            'spider',
            'facebookexternalhit',
            'twitterbot',
            'whatsapp',
            'telegrambot',
            'linkedinbot',
            'slackbot',
            'discordbot',
            'googlebot',
        ];

        foreach ($botSignatures as $signature) {
            if ($ua !== '' && str_contains($ua, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function buildProfilePostsUrl(Post $post, string $hashId): string
    {
        $username = (string) ($post->user?->username ?? '');
        if ($username === '') {
            return url("publication/{$hashId}?seo=1");
        }

        return url("/@{$username}/posts?post={$hashId}");
    }
}
