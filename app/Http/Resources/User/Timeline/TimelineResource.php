<?php
/*
|--------------------------------------------------------------------------
| Zestex - Social Network Platform.
|--------------------------------------------------------------------------
| Based on: Zestex - The Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav
|--------------------------------------------------------------------------
| Branded by: Vicky Bedardi Yadav
| E-mail: vicktbedardi9@gmail.com
|--------------------------------------------------------------------------
| Copyright (c) Flip Basket Pvt Ltd. All rights reserved. 
|--------------------------------------------------------------------------
*/

namespace App\Http\Resources\User\Timeline;

use App\Support\Num;
use Illuminate\Http\Request;
use App\Http\Resources\User\Media\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\User\Timeline\PollResource;
use App\Http\Resources\User\Timeline\QuoteResource;
use App\Http\Resources\User\User\UserPreviewResource;
use App\Http\Resources\User\Morph\LinkSnapshotResource;
use App\Http\Resources\User\Timeline\ReactionCollection;
use App\Models\Post;
use App\Models\User;

class TimelineResource extends JsonResource
{
    public function toArray(Request $request):array
    {
        $auth = auth_check();
        $meUser = $auth ? me() : null;
        $isOwner = $auth && $meUser ? ($meUser->id === $this->user_id) : false;

        $apiData = [
            'id' => $this->id,
            'content' => e($this->content),
            'type' => $this->type,
            'text_language' => $this->text_language,
            'hash_id' => $this->hash_id,
            'relations' => [
                'user' => UserPreviewResource::make($this->user),
                'reactions' => ReactionCollection::make($this->reactions),
                'comments' => $this->getPreviewComments()
            ],
            'views_count' => [
                'raw' => $this->views_count,
                'formatted' => Num::abbreviate($this->views_count)
            ],
            'comments_count' => [
                'raw' => $this->comments_count,
                'formatted' => Num::abbreviate($this->comments_count)
            ],
            'quotes_count' => [
                'raw' => $this->quotes_count,
                'formatted' => Num::abbreviate($this->quotes_count)
            ],
            'date' => [
                'iso' => $this->created_at->getIso(),
                'time_ago' => $this->created_at->getTimeAgo(),
                'timestamp' => $this->created_at->getTimestamp()
            ],
            'meta' => [
                'permissions' => [
                    'can_like' => $auth,
                    'can_comment' => $auth,
                    'is_admin' => $auth && $meUser ? $meUser->isAdmin() : false,
                    'can_edit' => $auth && $meUser ? $meUser->can('update', $this->resource) : false,
                    'can_delete' => $auth && $meUser ? $meUser->can('delete', $this->resource) : false,
                    'can_report' => $auth ? empty($isOwner) : false
                ],
                'activity' => [
                    'bookmarked' => $auth && $meUser ? $this->isBookmarkedBy($meUser->id) : false,
                    'reposted' => $auth && $meUser ? $this->isRepostedBy($meUser->id) : false
                ],
                'engagement' => [
                    'liked_by' => $this->getPreviewReactionUsers(),
                    'shared_by' => $this->getPreviewShareUsers(),
                ],
                'is_translatable' => $this->isContentTranslatable(),
                'is_quoting' => $this->is_quoting,
                'is_sensitive' => $this->is_sensitive,
                'is_ai_generated' => $this->is_ai_generated,
                'is_agent_post' => (bool) optional($this->user)->isAiAgent(),
                'is_live_stream' => $this->isLiveStreamPost(),
                'is_promoted' => false,
                'promoted_label' => 'Ad',
                'ad_id' => null,
                'campaign_id' => null,
                'placement' => null,
                'cta' => null,
                'target_url' => null,
                'rank' => $this->getRankMeta(),
            ],
        ];

        if($this->type->isMedia()) {
            $apiData['relations']['media'] = $this->media->map(function($item) {
                return MediaResource::make($item);
            });
        }

        else if($this->type->isPoll()) {
            $apiData['relations']['poll'] = PollResource::make($this->poll);
        }

        if($this->quotedPost) {
            $apiData['relations']['quoted_post'] = QuoteResource::make($this->quotedPost);
        }

        if($this->linkSnapshot) {
            $apiData['relations']['link_snapshot'] = LinkSnapshotResource::make($this->linkSnapshot);
        }
        
        return $apiData;
    }

    private function getPreviewComments(): array
    {
        return $this->comments->unique('user.id')->map(function($item) {
            return [
                'id' => $item->id,
                'user' => [
                    'avatar_url' => $item->user->avatar_url
                ]
            ];  
        })->toArray();
    }

    private function getPreviewReactionUsers(): array
    {
        $reaction = $this->reactions->first();
        if (!$reaction || empty($reaction->users)) {
            return [];
        }

        $userIds = collect($reaction->users)->map(fn ($id) => (int) $id)->unique()->take(5)->values()->all();
        if (empty($userIds)) {
            return [];
        }

        return User::whereIn('id', $userIds)
            ->select('id', 'username', 'first_name', 'last_name', 'avatar')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'avatar_url' => $user->avatar_url,
                ];
            })->toArray();
    }

    private function getPreviewShareUsers(): array
    {
        $shareUserIds = Post::query()
            ->where('quote_post_id', $this->id)
            ->where('is_quoting', true)
            ->latest('id')
            ->limit(5)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        if (empty($shareUserIds)) {
            return [];
        }

        return User::whereIn('id', $shareUserIds)
            ->select('id', 'username', 'first_name', 'last_name', 'avatar')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'avatar_url' => $user->avatar_url,
                ];
            })->toArray();
    }

    private function getRankMeta(): ?array
    {
        $meta = null;

        if (is_object($this->resource) && method_exists($this->resource, 'getAttribute')) {
            $meta = $this->resource->getAttribute('__rank_meta');
        }

        if (! is_array($meta)) {
            $meta = data_get($this->resource, '__rank_meta');
        }

        if (! is_array($meta)) {
            return null;
        }

        return [
            'algorithm' => data_get($meta, 'algorithm'),
            'score' => round((float) data_get($meta, 'score', 0), 4),
            'base_score' => round((float) data_get($meta, 'base_score', 0), 4),
            'tier' => data_get($meta, 'tier', 'needs_boost'),
            'eligibility' => [
                'trending' => (bool) data_get($meta, 'eligibility.trending', false),
                'recommended' => (bool) data_get($meta, 'eligibility.recommended', false),
                'viral_watch' => (bool) data_get($meta, 'eligibility.viral_watch', false),
                'top_feed' => (bool) data_get($meta, 'eligibility.top_feed', false),
                'controversial_feed' => (bool) data_get($meta, 'eligibility.controversial_feed', false),
            ],
            'scores' => [
                'hot' => round((float) data_get($meta, 'scores.hot', 0), 4),
                'new' => round((float) data_get($meta, 'scores.new', 0), 4),
                'top' => round((float) data_get($meta, 'scores.top', 0), 4),
                'rising' => round((float) data_get($meta, 'scores.rising', 0), 4),
                'controversial' => round((float) data_get($meta, 'scores.controversial', 0), 4),
                'best' => round((float) data_get($meta, 'scores.best', 0), 4),
                'engagement' => round((float) data_get($meta, 'scores.engagement', 0), 4),
            ],
        ];
    }

    private function isLiveStreamPost(): bool
    {
        $content = trim((string) $this->content);

        if ($content === '') {
            return false;
        }

        return (bool) preg_match('/^(?:🔴\s*)?LIVE NOW:/iu', $content);
    }
}

