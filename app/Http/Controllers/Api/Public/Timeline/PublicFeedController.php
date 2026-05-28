<?php
/*
|--------------------------------------------------------------------------
| Public Timeline Controller (Guest Mode)
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\Api\Public\Timeline;

use App\Models\Post;
use App\Enums\User\UserType;
use Illuminate\Http\Request;
use App\Services\Feed\FeedContextResolver;
use App\Services\Feed\FeedRanker;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Http\Resources\User\Timeline\TimelineResource;
use App\Http\Resources\User\Timeline\CommentCollection;
use App\Http\Resources\User\Timeline\TimelineCollection;
use App\Http\Resources\User\Overview\UserOverviewResource;

class PublicFeedController extends Controller
{
    use SupportsApiResponses;

    private array $filter = [];

    public function getFeed(Request $request)
    {
        $filter = $request->array('filter');

        $this->filter['page'] = data_get_integer($filter, 'page', 1);
        $this->filter['onset'] = data_get_integer($filter, 'onset', 0);
        $this->filter['sort'] = data_get($filter, 'sort', config('feed-ranking.default_sort', FeedRanker::SORT_HOT));

        $ranker = app(FeedRanker::class);
        $context = app(FeedContextResolver::class)->resolve($request, null, 'timeline_public');
        $sort = $ranker->normalizeSort((string) $this->filter['sort']);
        $perPage = (int) config('post.paginate_per');
        $candidateLimit = max(
            (int) config('feed-ranking.candidate_min', 120),
            $perPage * (int) config('feed-ranking.candidate_multiplier', 12)
        );

        $feedORMQuery = Post::timelineFormatPosts()
            ->when(! empty($this->filter['onset']), function ($query) {
                $query->where('id', '>', $this->filter['onset']);
            })
            ->whereHas('user', function ($u) {
                $u->where(function ($query) {
                    $query->author()->orWhere('type', UserType::AI_AGENT->value);
                });
            });

        $timelinePosts = $ranker->rank(
            $feedORMQuery->latest('id')->take($candidateLimit)->get(),
            $sort,
            $context
        )->forPage($this->filter['page'], $perPage)->values();

        return $this->responseSuccess([
            'data' => TimelineCollection::make($timelinePosts),
            'meta' => [
                'sort' => $sort,
                'supported_sorts' => $ranker->supportedSorts(),
                'context' => $context,
            ],
        ]);
    }

    public function getPostData(Request $request)
    {
        $postHashId = $request->route('hashId');

        $postData = Post::active()->whereHashId($postHashId)->timelineFormatPosts()->first();

        if ($postData) {
            $postComments = $this->fetchPostItemComments($postData);

            return $this->responseSuccess([
                'data' => [
                    'author' => UserOverviewResource::make($postData->user),
                    'post' => TimelineResource::make($postData),
                    'comments' => CommentCollection::make($postComments),
                    'meta' => [
                        'comments_per_page' => config('post.comments.paginate_per'),
                    ],
                ],
            ]);
        }

        return $this->responseResourceNotFoundError('Post', $postHashId);
    }

    public function getPostComments(Request $request)
    {
        $postHashId = $request->route('hashId');
        $cursorId = $request->integer('cursor');

        $postData = Post::active()->whereHashId($postHashId)->first();

        if (empty($postData)) {
            return $this->responseResourceNotFoundError('Post', $postHashId);
        }

        $postComments = $this->fetchPostItemComments($postData, $cursorId);

        return $this->responseSuccess([
            'data' => CommentCollection::make($postComments),
        ]);
    }

    private function fetchPostItemComments(Post $postData, int|string $cursorId = 0)
    {
        $postComments = $postData->comments()->with([
            'post:id,user_id',
            'user:id,first_name,last_name,avatar,username',
            'reactions',
            'parent.user:id,first_name,last_name,username',
        ])->when($cursorId, function ($query) use ($cursorId) {
            $query->where('id', '<', $cursorId);
        })->latest('id');

        return $postComments->take(config('post.comments.paginate_per'))->get();
    }
}
