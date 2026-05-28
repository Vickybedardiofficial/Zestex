<?php

namespace App\Http\Controllers\Api\Public\Search;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use App\Database\Configs\Table;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Http\Resources\User\Timeline\TimelineCollection;
use App\Http\Resources\User\People\PeopleCollection;

class SearchController extends Controller
{
    use SupportsApiResponses;

    /**
     * Search posts by query (hashtag, keyword, user mention)
     */
    public function searchPosts(Request $request)
    {
        $query = (string) $request->input('q', '');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));

        if (empty($query)) {
            return $this->responseError(['message' => 'Query parameter is required'], 400);
        }

        $searchPattern = '%' . addcslashes($query, '%_') . '%';

        $posts = Post::active()
            ->where('created_at', '>=', now()->subDays(90))
            ->where(function ($q) use ($searchPattern) {
                $q->whereRaw("LOWER(content) LIKE LOWER(?)", [$searchPattern]);
            })
            ->with([
                'user:id,username,avatar,first_name,last_name,verified',
                'reactions',
                'comments:id,post_id,user_id'
            ])
            ->orderBy('created_at', 'desc')
            ->orderBy('comments_count', 'desc')
            ->orderBy('bookmarks_count', 'desc')
            ->simplePaginateManual($perPage, $page);

        return $this->responseSuccess([
            'data' => TimelineCollection::make($posts->items()),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'has_more' => $posts->hasMorePages(),
            ],
            'query' => $query,
        ]);
    }

    /**
     * Search people by username, name, bio
     */
    public function searchPeople(Request $request)
    {
        $query = (string) $request->input('q', '');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));

        if (empty($query)) {
            return $this->responseError(['message' => 'Query parameter is required'], 400);
        }

        $people = User::active()
            ->author()
            ->where(function ($q) use ($query) {
                $q->whereLike('username', "%{$query}%")
                    ->orWhereLike('first_name', "%{$query}%")
                    ->orWhereLike('last_name', "%{$query}%")
                    ->orWhereLike('bio', "%{$query}%")
                    ->orWhereLike('caption', "%{$query}%");
            })
            ->orderByDesc('followers_count')
            ->orderByDesc('publications_count')
            ->simplePaginateManual($perPage, $page);

        return $this->responseSuccess([
            'data' => PeopleCollection::make($people->items()),
            'pagination' => [
                'current_page' => $people->currentPage(),
                'per_page' => $people->perPage(),
                'has_more' => $people->hasMorePages(),
            ],
            'query' => $query,
        ]);
    }

    /**
     * Search hashtags from posts
     */
    public function searchHashtags(Request $request)
    {
        $query = (string) $request->input('q', '');

        if (empty($query)) {
            return $this->responseError(['message' => 'Query parameter is required'], 400);
        }

        $searchPattern = '%#' . addcslashes(str_replace('#', '', $query), '%_') . '%';

        $posts = Post::active()
            ->where('created_at', '>=', now()->subDays(30))
            ->whereRaw("LOWER(content) LIKE LOWER(?)", [$searchPattern])
            ->get(['id', 'content', 'created_at']);

        $hashtagMatches = [];
        foreach ($posts as $post) {
            if (preg_match_all('/[#]([a-zA-Z0-9_\u0600-\u06FF]+)/u', $post->content, $matches)) {
                foreach ($matches[1] as $tag) {
                    $hashtag = '#' . strtolower($tag);
                    if (stripos($hashtag, $query) !== false) {
                        $hashtagMatches[$hashtag] = ($hashtagMatches[$hashtag] ?? 0) + 1;
                    }
                }
            }
        }

        arsort($hashtagMatches);

        $results = array_slice($hashtagMatches, 0, 20);

        return $this->responseSuccess([
            'data' => array_map(function ($hashtag, $count) {
                return [
                    'hashtag' => $hashtag,
                    'post_count' => $count,
                ];
            }, array_keys($results), array_values($results)),
            'query' => $query,
        ]);
    }
}
