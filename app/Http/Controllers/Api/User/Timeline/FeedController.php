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

namespace App\Http\Controllers\Api\User\Timeline;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Enums\Post\PostStatus;
use App\Enums\User\UserType;
use App\Database\Configs\Table;
use App\Services\Feed\FeedContextResolver;
use App\Services\Feed\FeedRanker;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Http\Resources\User\Timeline\TimelineResource;
use App\Http\Resources\User\Timeline\CommentCollection;
use App\Http\Resources\User\Timeline\TimelineCollection;
use App\Http\Resources\User\Overview\UserOverviewResource;

class FeedController extends Controller
{
    use SupportsApiResponses;

    private $me = null;
    private $filter = [];

    public function __construct()
    {
        $this->me = me();
    }

    public function getFeed(Request $request)
    {
        if (! $this->me) {
            return $this->responseUnauthorizedError();
        }

        $filter = $request->array('filter');

        $this->filter['page'] = data_get_integer($filter, 'page', 1);
        $this->filter['onset'] = data_get_integer($filter, 'onset', 0);
        $this->filter['sort'] = data_get($filter, 'sort', config('feed-ranking.default_sort', FeedRanker::SORT_HOT));

        $ranker = app(FeedRanker::class);
        $context = app(FeedContextResolver::class)->resolve($request, $this->me, 'timeline');
        $sort = $ranker->normalizeSort((string) $this->filter['sort']);
        $perPage = (int) config('post.paginate_per');
        $candidateLimit = max(
            (int) config('feed-ranking.candidate_min', 120),
            $perPage * (int) config('feed-ranking.candidate_multiplier', 12)
        );

        $processingPosts = $this->me->posts()->where('status', PostStatus::PROCESSING_VIDEO->value)->get();

        $feedORMQuery = Post::timelineFormatPosts()
            ->excludeBlockedUsers($this->me->id)
            ->when(! empty($this->filter['onset']), function($query) {
                $query->where('id', '>', $this->filter['onset']);
            })->when((! $this->me->isAdmin()), function($query) {
                $query->where(function($query) {
                    $query->where('user_id', $this->me->id)
                        ->orWhereHas('user', function($u) {
                            $u->whereIn('user_id', function($query) {
                                $query->select('following_id')->from(Table::FOLLOWS)->where('follower_id', $this->me->id);
                            })->author();
                        })
                        ->orWhereHas('user', function($u) {
                            $u->where('type', UserType::AI_AGENT->value);
                        });
                });
            });

        $timelinePosts = $ranker->rank(
            $feedORMQuery->latest('id')->take($candidateLimit)->get(),
            $sort,
            $context
        )->forPage($this->filter['page'], $perPage)->values();

        // If user has no posts and doesn't follow any authors, the feed becomes empty.
        // Fallback to a global authors feed on first page to avoid "no posts yet" for new users.
        if (
            $timelinePosts->isEmpty() &&
            (! $this->me->isAdmin()) &&
            empty($this->filter['onset']) &&
            ((int) $this->filter['page'] === 1)
        ) {
            $feedORMQuery = Post::timelineFormatPosts()
                ->excludeBlockedUsers($this->me->id)
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
        }

        $timelinePosts = $processingPosts->merge($timelinePosts)->unique('id')->values();
        
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

        $postData = Post::active()->whereHashId($postHashId)->timelineFormatPosts()->excludeBlockedUsers($this->me->id)->first();
        
        if($postData) {
            $postComments = $this->fetchPostItemComments($postData);

            return $this->responseSuccess([
                'data' => [
                    'author' => UserOverviewResource::make($postData->user),
                    'post' => TimelineResource::make($postData),
                    'comments' => CommentCollection::make($postComments),
                    'meta' => [
                        'comments_per_page' => config('post.comments.paginate_per')
                    ]
                ]
            ]);
        }

        else{
            return $this->responseResourceNotFoundError('Post', $postHashId);
        }
    }

    public function getPostComments(Request $request)
    {
        $postHashId = $request->route('hashId');
        $cursorId = $request->integer('cursor');

        $postData = Post::active()->whereHashId($postHashId)->excludeBlockedUsers($this->me->id)->first();

        if(empty($postData)) {
            return $this->responseResourceNotFoundError('Post', $postHashId);
        }

        $postComments = $this->fetchPostItemComments($postData, $cursorId);

        return $this->responseSuccess([
            'data' => CommentCollection::make($postComments)
        ]);
    }

    private function fetchPostItemComments(Post $postData, int|string $cursorId = 0)
    {   
        $postComments = $postData->comments()->with([
            'post:id,user_id',
            'user:id,first_name,last_name,avatar,username',
            'reactions',
            'parent.user:id,first_name,last_name,username'
        ])->whereNotIn('user_id', function ($query) {
            $query->select('blocked_id')->from(Table::USER_BLOCKS)->where('blocker_id', $this->me->id);
        })->whereNotIn('user_id', function ($query) {
            $query->select('blocker_id')->from(Table::USER_BLOCKS)->where('blocked_id', $this->me->id);
        })->when($cursorId, function($query) use ($cursorId) {
            $query->where('id', '<', $cursorId);
        })->latest('id');

        return $postComments->take(config('post.comments.paginate_per'))->get();
    }

}
