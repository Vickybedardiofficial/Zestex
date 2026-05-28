<?php

namespace App\Actions\Recommend;

use App\Models\User;
use App\Database\Configs\Table;

class FetchFollowRecommendation
{
    public function handle(int $limit = 5)
    {
        $baseQuery = User::active()->excludeSelf()->whereNotIn('id', function ($query) {
            $query->select('following_id')->from(Table::FOLLOWS)->where('follower_id', me()->id);
        });

        // Primary: suggest authors (intended UX).
        $recommendations = (clone $baseQuery)->author()
            ->limit($limit)
            ->orderByDesc('followers_count')
            ->orderByDesc('publications_count')
            ->inRandomOrder()
            ->get();

        // Fallback: if there are no authors in a fresh/local database, suggest any active users.
        if ($recommendations->isEmpty()) {
            $recommendations = (clone $baseQuery)
                ->limit($limit)
                ->orderByDesc('followers_count')
                ->orderByDesc('publications_count')
                ->inRandomOrder()
                ->get();
        }

        return $recommendations;
    }
}
