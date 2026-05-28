<?php

namespace App\Services\Feed;

use App\Models\AdminSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FeedRanker
{
    public const SORT_HOT = 'hot';
    public const SORT_NEW = 'new';
    public const SORT_TOP = 'top';
    public const SORT_RISING = 'rising';
    public const SORT_CONTROVERSIAL = 'controversial';
    public const SORT_BEST = 'best';
    protected static ?array $runtimeOverrides = null;

    /**
     * Rank posts using selected algorithm.
     */
    public function rank(Collection $posts, string $sort = self::SORT_HOT, array $context = []): Collection
    {
        $algorithm = $this->normalizeSort($sort);
        $context = $this->normalizeContext($context);

        return $posts
            ->map(function ($post) use ($algorithm, $context) {
                $meta = $this->evaluatePost($post, $algorithm, $context);
                $score = (float) $meta['score'];

                if (is_array($post)) {
                    $post['__rank_score'] = $score;
                    $post['__rank_meta'] = $meta;
                    return $post;
                }

                if (is_object($post) && method_exists($post, 'setAttribute')) {
                    $post->setAttribute('__rank_score', $score);
                    $post->setAttribute('__rank_meta', $meta);
                    return $post;
                }

                $post->__rank_score = $score;
                $post->__rank_meta = $meta;
                return $post;
            })
            ->sortByDesc(fn ($post) => (float) data_get($post, '__rank_score', 0))
            ->values();
    }

    public function normalizeSort(string $sort): string
    {
        $sort = strtolower(trim($sort));

        return in_array($sort, $this->supportedSorts(), true)
            ? $sort
            : (string) $this->cfg('default_sort', self::SORT_HOT);
    }

    /**
     * @return string[]
     */
    public function supportedSorts(): array
    {
        return [
            self::SORT_HOT,
            self::SORT_NEW,
            self::SORT_TOP,
            self::SORT_RISING,
            self::SORT_CONTROVERSIAL,
            self::SORT_BEST,
        ];
    }

    public function score($post, string $sort, array $context = []): float
    {
        $scores = $this->allScores($post);
        $baseScore = (float) data_get($scores, $sort, 0.0);

        return $this->applyContextBoost($baseScore, $post, $sort, $context, $scores);
    }

    public function evaluatePost($post, string $sort, array $context = []): array
    {
        $scores = $this->allScores($post);
        $engagement = (float) $scores['engagement'];
        $best = (float) $scores[self::SORT_BEST];
        $hot = (float) $scores[self::SORT_HOT];
        $rising = (float) $scores[self::SORT_RISING];
        $baseScore = (float) data_get($scores, $sort, 0.0);
        $boostedScore = $this->applyContextBoost($baseScore, $post, $sort, $context, $scores);

        $thresholds = (array) $this->cfg('thresholds', []);
        $trendingEligible = (
            $engagement >= (float) data_get($thresholds, 'engagement.minimum_for_trending', 45.0)
            && $best >= (float) data_get($thresholds, 'best.minimum_for_trending', 0.60)
            && (
                $hot >= (float) data_get($thresholds, 'hot.minimum_for_trending', 75.0)
                || $rising >= (float) data_get($thresholds, 'rising.minimum_for_trending', 100.0)
            )
        );
        $recommendedEligible = (
            $engagement >= (float) data_get($thresholds, 'engagement.minimum_for_recommendation', 20.0)
            && $best >= (float) data_get($thresholds, 'best.minimum_for_recommendation', 0.45)
            && (
                $hot >= (float) data_get($thresholds, 'hot.minimum_for_recommendation', 40.0)
                || $rising >= (float) data_get($thresholds, 'rising.minimum_for_recommendation', 20.0)
            )
        );
        $viralWatch = $rising >= (float) data_get($thresholds, 'rising.minimum_for_viral_watch', 60.0);

        return [
            'algorithm' => $sort,
            'score' => $boostedScore,
            'base_score' => $baseScore,
            'scores' => $scores,
            'context' => $this->normalizeContext($context),
            'eligibility' => [
                'trending' => $trendingEligible,
                'recommended' => $recommendedEligible,
                'viral_watch' => $viralWatch,
                'top_feed' => ((float) $scores[self::SORT_TOP]) >= (float) data_get($thresholds, 'top.minimum_for_top_feed', 65.0),
                'controversial_feed' => ((float) $scores[self::SORT_CONTROVERSIAL]) >= (float) data_get($thresholds, 'controversial.minimum_for_controversial_feed', 12.0),
            ],
            'tier' => $trendingEligible
                ? 'trending'
                : ($recommendedEligible ? 'recommended' : ($viralWatch ? 'watchlist' : 'needs_boost')),
        ];
    }

    protected function allScores($post): array
    {
        $engagement = $this->engagement($post);
        $best = $this->scoreBest($post);

        return [
            self::SORT_NEW => $this->scoreNew($post),
            self::SORT_TOP => $this->scoreTop($post),
            self::SORT_RISING => $this->scoreRising($post),
            self::SORT_CONTROVERSIAL => $this->scoreControversial($post),
            self::SORT_BEST => $best,
            self::SORT_HOT => $this->scoreHot($post),
            'engagement' => $engagement,
        ];
    }

    protected function legacyBaseScore($post, string $sort): float
    {
        return match ($sort) {
            self::SORT_NEW => $this->scoreNew($post),
            self::SORT_TOP => $this->scoreTop($post),
            self::SORT_RISING => $this->scoreRising($post),
            self::SORT_CONTROVERSIAL => $this->scoreControversial($post),
            self::SORT_BEST => $this->scoreBest($post),
            default => $this->scoreHot($post),
        };
    }

    /**
     * Wilson lower bound (best quality by confidence).
     */
    protected function scoreBest($post): float
    {
        [$upvotes, $downvotes] = $this->votes($post);
        $n = $upvotes + $downvotes;

        if ($n <= 0) {
            return 0.0;
        }

        $z = (float) $this->cfg('wilson_z', 1.96);
        $p = $upvotes / $n;
        $z2 = $z * $z;
        $left = $p + ($z2 / (2 * $n));
        $right = $z * sqrt((($p * (1 - $p)) + ($z2 / (4 * $n))) / $n);
        $under = 1 + ($z2 / $n);

        return ($left - $right) / $under;
    }

    /**
     * Newest first.
     */
    protected function scoreNew($post): float
    {
        return (float) $this->createdAt($post)->timestamp;
    }

    /**
     * Pure quality/engagement driven.
     */
    protected function scoreTop($post): float
    {
        [$upvotes, $downvotes] = $this->votes($post);
        $netVotes = max(0, $upvotes - $downvotes);
        $engagement = $this->engagement($post);
        $best = $this->scoreBest($post);

        return ($best * 100.0)
            + ($netVotes * (float) $this->cfg('top.vote_weight', 1.0))
            + ($engagement * (float) $this->cfg('top.engagement_weight', 0.2));
    }

    /**
     * Hot = quality + engagement - time decay.
     */
    protected function scoreHot($post): float
    {
        $ageHours = $this->ageHours($post);
        $best = $this->scoreBest($post);
        $engagement = $this->engagement($post);

        return ($best * 120.0)
            + ($engagement * (float) $this->cfg('hot.engagement_weight', 0.25))
            - ($ageHours * (float) $this->cfg('hot.time_decay_per_hour', 1.2));
    }

    /**
     * Rising = velocity * confidence (avoid lucky one-offs).
     */
    protected function scoreRising($post): float
    {
        [$upvotes, $downvotes] = $this->votes($post);
        $n = max(0, $upvotes + $downvotes);
        $engagement = $this->engagement($post);
        $ageHours = max((float) $this->cfg('rising.min_age_hours', 0.5), $this->ageHours($post));
        $velocity = ($n + ($engagement * (float) $this->cfg('rising.engagement_multiplier', 0.5))) / $ageHours;
        $confidence = $this->scoreBest($post);

        return $velocity * (1.0 + $confidence);
    }

    /**
     * Controversial = lots of votes and split opinion.
     */
    protected function scoreControversial($post): float
    {
        [$upvotes, $downvotes] = $this->votes($post);
        $n = $upvotes + $downvotes;

        if ($n <= 0) {
            return 0.0;
        }

        $balance = 1.0 - (abs($upvotes - $downvotes) / $n); // 1 when 50/50
        $agePenalty = 1.0 + ($this->ageHours($post) / (float) $this->cfg('controversial.age_penalty_hours', 24.0));

        return ($n * $balance) / $agePenalty;
    }

    protected function engagement($post): float
    {
        $comments = (int) data_get($post, 'comments_count', 0);
        $bookmarks = (int) data_get($post, 'bookmarks_count', 0);
        $views = (int) data_get($post, 'views_count', 0);
        $quotes = (int) data_get($post, 'quotes_count', 0);
        $reactions = (int) data_get($post, 'reactions_count', 0);

        if ($reactions === 0) {
            $reactions = (int) collect(data_get($post, 'reactions', []))
                ->sum(fn ($reaction) => (int) data_get($reaction, 'reactions_count', 0));
        }

        return ($comments * 2.0)
            + ($bookmarks * 3.0)
            + ($views * 0.1)
            + ($quotes * 2.0)
            + ($reactions * 1.5);
    }

    /**
     * Derive vote counts with graceful fallbacks.
     */
    protected function votes($post): array
    {
        $reactions = collect(data_get($post, 'reactions', []));
        $totalReactions = (int) $reactions->sum(fn ($reaction) => (int) data_get($reaction, 'reactions_count', 0));
        $thumbsUp = (int) $reactions
            ->filter(fn ($reaction) => strtolower((string) data_get($reaction, 'unified_id')) === '1f44d')
            ->sum(fn ($reaction) => (int) data_get($reaction, 'reactions_count', 0));
        $thumbsDown = (int) $reactions
            ->filter(fn ($reaction) => strtolower((string) data_get($reaction, 'unified_id')) === '1f44e')
            ->sum(fn ($reaction) => (int) data_get($reaction, 'reactions_count', 0));

        $upvotes = (int) data_get($post, 'upvotes', $thumbsUp > 0 ? $thumbsUp : $totalReactions);
        $downvotes = (int) data_get($post, 'downvotes', $thumbsDown);

        if ($downvotes === 0 && $totalReactions > $upvotes) {
            $downvotes = max(0, $totalReactions - $upvotes);
        }

        $upvotes = max(0, $upvotes);
        $downvotes = max(0, $downvotes);

        return [$upvotes, $downvotes];
    }

    protected function ageHours($post): float
    {
        $createdAt = $this->createdAt($post);

        return max(0.01, now()->floatDiffInHours($createdAt));
    }

    protected function createdAt($post): Carbon
    {
        $value = data_get($post, 'created_at');

        if ($value instanceof Carbon) {
            return $value;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable $exception) {
            return now();
        }
    }

    protected function normalizeContext(array $context): array
    {
        return [
            'feature' => strtolower((string) ($context['feature'] ?? 'timeline')),
            'country' => strtoupper((string) ($context['country'] ?? '')),
            'city' => strtolower((string) ($context['city'] ?? '')),
            'area' => strtolower((string) ($context['area'] ?? '')),
            'language' => strtolower((string) ($context['language'] ?? '')),
        ];
    }

    protected function applyContextBoost(float $score, $post, string $sort, array $context, array $scores = []): float
    {
        $feature = (string) ($context['feature'] ?? 'timeline');
        $featureMultiplier = (float) data_get(
            $this->cfg('feature_multipliers', []),
            "{$feature}.{$sort}",
            1.0
        );

        $geoMultiplier = $this->geoAffinityMultiplier($post, $context);
        $languageMultiplier = $this->languageAffinityMultiplier($post, $context);
        $creatorMultiplier = $this->newCreatorBoostMultiplier($post, $scores);

        return $score * $featureMultiplier * $geoMultiplier * $languageMultiplier * $creatorMultiplier;
    }

    protected function geoAffinityMultiplier($post, array $context): float
    {
        $contextCountry = (string) ($context['country'] ?? '');
        $contextCity = (string) ($context['city'] ?? '');
        $contextArea = (string) ($context['area'] ?? '');

        $postCountry = strtoupper((string) (
            data_get($post, 'user.country')
            ?? data_get($post, 'country')
            ?? ''
        ));
        $postCity = strtolower((string) (
            data_get($post, 'user.city')
            ?? data_get($post, 'city')
            ?? ''
        ));
        $postArea = strtolower((string) (
            data_get($post, 'user.region')
            ?? data_get($post, 'region')
            ?? data_get($post, 'area')
            ?? ''
        ));

        $multiplier = 1.0;

        if ($contextCountry !== '' && $postCountry !== '') {
            if ($contextCountry === $postCountry) {
                $multiplier += (float) $this->cfg('context.country_match_boost', 0.12);
            } else {
                $multiplier *= (float) $this->cfg('context.cross_country_multiplier', 0.9);
            }
        }

        if ($contextCity !== '' && $postCity !== '') {
            if ($contextCity === $postCity) {
                $multiplier += (float) $this->cfg('context.city_match_boost', 0.08);
            }
        }

        if ($contextArea !== '' && $postArea !== '') {
            if ($contextArea === $postArea) {
                $multiplier += (float) $this->cfg('context.area_match_boost', 0.06);
            }
        }

        return max(0.5, $multiplier);
    }

    protected function languageAffinityMultiplier($post, array $context): float
    {
        $contextLang = strtolower((string) ($context['language'] ?? ''));
        $postLang = strtolower((string) (
            data_get($post, 'text_language')
            ?? data_get($post, 'language')
            ?? ''
        ));

        if ($contextLang === '' || $postLang === '') {
            return 1.0;
        }

        $contextBase = substr($contextLang, 0, 2);
        $postBase = substr($postLang, 0, 2);

        if ($contextLang === $postLang || ($contextBase !== '' && $contextBase === $postBase)) {
            return (float) $this->cfg('context.language_match_multiplier', 1.08);
        }

        return (float) $this->cfg('context.language_mismatch_multiplier', 0.95);
    }

    protected function newCreatorBoostMultiplier($post, array $scores = []): float
    {
        if (! (bool) $this->cfg('new_creator.enabled', true)) {
            return 1.0;
        }

        $createdAt = data_get($post, 'user.created_at');
        if (! $createdAt) {
            return 1.0;
        }

        try {
            $hours = now()->floatDiffInHours(Carbon::parse((string) $createdAt));
        } catch (\Throwable $exception) {
            return 1.0;
        }

        if ($hours > (float) $this->cfg('new_creator.max_account_age_hours', 72)) {
            return 1.0;
        }

        $best = (float) data_get($scores, self::SORT_BEST, $this->scoreBest($post));
        $engagement = (float) data_get($scores, 'engagement', $this->engagement($post));

        if (
            $best < (float) $this->cfg('new_creator.quality_gate_best_score', 0.35)
            || $engagement < (float) $this->cfg('new_creator.quality_gate_engagement', 8.0)
        ) {
            return 1.0;
        }

        return (float) $this->cfg('new_creator.boost_multiplier', 1.12);
    }

    protected function cfg(string $path, mixed $default = null): mixed
    {
        $base = config("feed-ranking.{$path}", $default);
        $key = 'feed_ranking_' . str_replace('.', '_', $path);
        $overrides = $this->runtimeOverrides();

        if (! array_key_exists($key, $overrides)) {
            return $base;
        }

        $value = $overrides[$key];
        if ($value === null || $value === '') {
            return $base;
        }

        if (is_bool($base)) {
            return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
        }

        if (is_int($base)) {
            return (int) $value;
        }

        if (is_float($base)) {
            return (float) $value;
        }

        return $value;
    }

    protected function runtimeOverrides(): array
    {
        if (self::$runtimeOverrides !== null) {
            return self::$runtimeOverrides;
        }

        self::$runtimeOverrides = Cache::remember('feed_ranking_runtime_overrides_v1', 60, function () {
            return AdminSetting::query()
                ->where('key', 'LIKE', 'feed_ranking_%')
                ->pluck('value', 'key')
                ->toArray();
        });

        return self::$runtimeOverrides;
    }
}
