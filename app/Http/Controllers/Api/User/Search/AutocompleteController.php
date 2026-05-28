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

namespace App\Http\Controllers\Api\User\Search;

use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\JobListing;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;

class AutocompleteController extends Controller
{
    use SupportsApiResponses;

    public function searchGlobal(Request $request)
    {
        $validated = validator([
            'query' => $request->input('query')
        ], [
            'query' => ['required', 'string', 'min:1', 'max:100']
        ])->validate();

        $query = trim($validated['query']);
        $search = $this->parseSearchQuery($query);
        $queryTerm = $search['term'];

        $users = User::active()
            ->where(function ($builder) use ($queryTerm) {
                $builder->whereLike('username', "{$queryTerm}%")
                    ->orWhereLike('username', "%{$queryTerm}%")
                    ->orWhereLike('first_name', "%{$queryTerm}%")
                    ->orWhereLike('last_name', "%{$queryTerm}%");
            })
            ->orderByRaw("CASE WHEN username LIKE ? THEN 0 ELSE 1 END", ["{$queryTerm}%"])
            ->limit(5)
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'type' => 'user',
                    'name' => $user->name,
                    'username' => $user->username,
                    'caption' => $user->caption,
                    'avatar_url' => $user->avatar_url,
                    'url' => $user->profile_url,
                ];
            });

        $posts = Post::active()
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->where(function ($builder) use ($search, $query, $queryTerm) {
                if ($search['is_hashtag']) {
                    $builder->whereRaw(
                        'LOWER(content) REGEXP ?',
                        [$this->buildHashtagRegex($search['tag'])]
                    );
                    return;
                }

                if ($search['is_mention']) {
                    $builder->whereRaw(
                        'LOWER(content) REGEXP ?',
                        [$this->buildMentionRegex($search['mention'])]
                    );
                    return;
                }

                $builder->whereLike('content', "%{$query}%");
                if ($queryTerm !== $query) {
                    $builder->orWhereLike('content', "%{$queryTerm}%");
                }
            })
            ->with('user:id,username,first_name,last_name,avatar,caption')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (Post $post) {
                return [
                    'id' => $post->id,
                    'type' => 'post',
                    'title' => str($post->content)->stripTags()->squish()->limit(110)->toString(),
                    'username' => $post->user?->username,
                    'author_name' => $post->user?->name,
                    'avatar_url' => $post->user?->avatar_url,
                    'url' => $post->url,
                ];
            });

        $products = Product::query()
            ->listable()
            ->where(function ($builder) use ($query) {
                $builder->whereLike('title', "%{$query}%")
                    ->orWhereLike('description', "%{$query}%");
            })
            ->with(['media'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'type' => 'product',
                    'title' => $product->title,
                    'price' => $product->formatted_price,
                    'preview_image_url' => $product->preview_image_url,
                    'url' => $product->url,
                ];
            });

        $jobs = JobListing::query()
            ->listable()
            ->where(function ($builder) use ($query) {
                $builder->whereLike('title', "%{$query}%")
                    ->orWhereLike('overview', "%{$query}%")
                    ->orWhereLike('description', "%{$query}%");
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (JobListing $job) {
                return [
                    'id' => $job->id,
                    'type' => 'job',
                    'title' => $job->title,
                    'income' => $job->formatted_income,
                    'currency' => $job->currency,
                    'url' => $job->url,
                ];
            });

        return $this->responseSuccess([
            'data' => [
                'users' => $users,
                'posts' => $posts,
                'products' => $products,
                'jobs' => $jobs,
            ]
        ]);
    }

    public function searchMentions(Request $request)
    {
        $searchResults = [];
        $validated = validator([
            'query' => $request->input('query')
        ], [
            'query' => ['required', 'string', 'min:1', 'max:255']
        ])->validate();

        if($validated['query']) {
            $query = trim($validated['query']);

            $mentionedUsers = User::active()
                ->where(function ($builder) use ($query) {
                    $builder->whereLike('username', "{$query}%")
                        ->orWhereLike('username', "%{$query}%");
                })
                ->orderByRaw("CASE WHEN username LIKE ? THEN 0 ELSE 1 END", ["{$query}%"])
                ->limit(50)
                ->get();
            
            if($mentionedUsers->isNotEmpty()) {
                $searchResults = $mentionedUsers->map(function($user) {
                    return [
                        'id' => $user->id,
                        'username' => $user->username,
                        'name' => $user->name,
                        'avatar_url' => $user->avatar_url,
                        'caption' => $user->caption,
                    ];
                });
            }
        }

        return $this->responseSuccess([
            'data' => $searchResults
        ]);
    }

    public function searchPage(Request $request)
    {
        $validated = validator([
            'query' => $request->input('query'),
            'filter' => $request->input('filter', 'top'),
            'limit' => $request->input('limit', 20),
            'page' => $request->input('page', 1),
        ], [
            'query' => ['required', 'string', 'min:1', 'max:100'],
            'filter' => ['nullable', 'string', 'in:top,latest,people,media,marketplace,jobs,lists'],
            'limit' => ['nullable', 'integer', 'min:5', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ])->validate();

        $query = trim($validated['query']);
        $search = $this->parseSearchQuery($query);
        $queryTerm = $search['term'];
        $filter = $validated['filter'] ?? 'top';
        $limit = (int) ($validated['limit'] ?? 20);
        $page = (int) ($validated['page'] ?? 1);

        $usersQuery = User::active()
            ->where(function ($builder) use ($queryTerm) {
                $builder->whereLike('username', "{$queryTerm}%")
                    ->orWhereLike('username', "%{$queryTerm}%")
                    ->orWhereLike('first_name', "%{$queryTerm}%")
                    ->orWhereLike('last_name', "%{$queryTerm}%");
            })
            ->orderByRaw("CASE WHEN username LIKE ? THEN 0 ELSE 1 END", ["{$queryTerm}%"]);

        $postsQuery = Post::active()
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->where(function ($builder) use ($search, $query, $queryTerm) {
                if ($search['is_hashtag']) {
                    $builder->whereRaw(
                        'LOWER(content) REGEXP ?',
                        [$this->buildHashtagRegex($search['tag'])]
                    );
                    return;
                }

                if ($search['is_mention']) {
                    $builder->whereRaw(
                        'LOWER(content) REGEXP ?',
                        [$this->buildMentionRegex($search['mention'])]
                    );
                    return;
                }

                $builder->whereLike('content', "%{$query}%");
                if ($queryTerm !== $query) {
                    $builder->orWhereLike('content', "%{$queryTerm}%");
                }
            })
            ->with(['user:id,username,first_name,last_name,avatar,caption', 'media:id,mediaable_id,mediaable_type,source_path,disk,thumbnail_path,thumbnail_disk'])
            ->withCount(['reactions', 'comments']);

        $productsQuery = Product::query()
            ->listable()
            ->where(function ($builder) use ($query) {
                $builder->whereLike('title', "%{$query}%")
                    ->orWhereLike('description', "%{$query}%");
            })
            ->with(['media']);

        $jobsQuery = JobListing::query()
            ->listable()
            ->where(function ($builder) use ($query) {
                $builder->whereLike('title', "%{$query}%")
                    ->orWhereLike('overview', "%{$query}%")
                    ->orWhereLike('description', "%{$query}%");
            });

        $users = collect();
        $posts = collect();
        $mediaPosts = collect();
        $products = collect();
        $jobs = collect();
        $postsHasMore = false;
        $mediaHasMore = false;
        $productsHasMore = false;
        $jobsHasMore = false;

        if (in_array($filter, ['top', 'people'], true)) {
            $usersLimit = $filter === 'top' ? 3 : $limit;

            $users = $usersQuery->limit($usersLimit)->get()->map(function (User $user) {
                $followersRaw = (int) ($user->followers_count ?? 0);
                $followingRaw = (int) ($user->following_count ?? 0);
                $isMe = auth()->check() && auth()->id() === $user->id;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'caption' => $user->caption,
                    'verified' => (bool) $user->verified,
                    'avatar_url' => $user->avatar_url,
                    'cover_url' => $user->cover_url,
                    'description' => (string) ($user->bio ?? ''),
                    'is_me' => $isMe,
                    'followers_count' => [
                        'raw' => $followersRaw,
                        'formatted' => number_format($followersRaw),
                    ],
                    'following_count' => [
                        'raw' => $followingRaw,
                        'formatted' => number_format($followingRaw),
                    ],
                    'meta' => [
                        'relationship' => [
                            'follow' => [
                                'following' => false,
                                'requested' => false,
                            ],
                        ],
                    ],
                    'url' => $user->profile_url,
                ];
            });
        }

        if (in_array($filter, ['top', 'latest'], true)) {
            $postRows = (clone $postsQuery)
                ->latest('id')
                ->forPage($page, ($limit + 1))
                ->get()
                ->values();

            $postsHasMore = $postRows->count() > $limit;
            $postRows = $postRows->slice(0, $limit);

            $posts = $postRows
                ->map(function (Post $post) {
                    return [
                        'id' => $post->id,
                        'title' => str($post->content)->stripTags()->squish()->limit(160)->toString(),
                        'username' => $post->user?->username,
                        'author_name' => $post->user?->name,
                        'avatar_url' => $post->user?->avatar_url,
                        'url' => $post->url,
                        'reactions_count' => $post->reactions_count,
                        'comments_count' => $post->comments_count,
                        'created_at' => $post->created_at?->diffForHumans(),
                    ];
                });
        }

        if (in_array($filter, ['top', 'media'], true)) {
            $mediaRows = (clone $postsQuery)
                ->whereHas('media')
                ->latest('id')
                ->forPage($page, ($limit + 1))
                ->get()
                ->values();

            $mediaHasMore = $mediaRows->count() > $limit;
            $mediaRows = $mediaRows->slice(0, $limit);

            $mediaPosts = $mediaRows
                ->map(function (Post $post) {
                    $firstMedia = $post->media->first();

                    return [
                        'id' => $post->id,
                        'title' => str($post->content)->stripTags()->squish()->limit(140)->toString(),
                        'username' => $post->user?->username,
                        'author_name' => $post->user?->name,
                        'avatar_url' => $post->user?->avatar_url,
                        'preview_image_url' => $firstMedia?->thumbnail_url ?? $firstMedia?->source_url,
                        'url' => $post->url,
                        'created_at' => $post->created_at?->diffForHumans(),
                    ];
                });
        }

        if (in_array($filter, ['top', 'marketplace'], true)) {
            $productRows = $productsQuery
                ->latest('id')
                ->forPage($page, ($limit + 1))
                ->get()
                ->values();

            $productsHasMore = $productRows->count() > $limit;
            $productRows = $productRows->slice(0, $limit);

            $products = $productRows
                ->map(function (Product $product) {
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'price' => $product->formatted_price,
                        'preview_image_url' => $product->preview_image_url,
                        'url' => $product->url,
                    ];
                });
        }

        if (in_array($filter, ['top', 'jobs'], true)) {
            $jobRows = $jobsQuery
                ->latest('id')
                ->forPage($page, ($limit + 1))
                ->get()
                ->values();

            $jobsHasMore = $jobRows->count() > $limit;
            $jobRows = $jobRows->slice(0, $limit);

            $jobs = $jobRows
                ->map(function (JobListing $job) {
                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'income' => $job->formatted_income,
                        'url' => $job->url,
                    ];
                });
        }

        return $this->responseSuccess([
            'data' => [
                'query' => $query,
                'filter' => $filter,
                'results' => [
                    'users' => $users,
                    'posts' => $posts,
                    'media' => $mediaPosts,
                    'products' => $products,
                    'jobs' => $jobs,
                    'lists' => [],
                ],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'has_more_posts' => $postsHasMore ?? false,
                    'has_more_media' => $mediaHasMore ?? false,
                    'has_more_products' => $productsHasMore ?? false,
                    'has_more_jobs' => $jobsHasMore ?? false,
                ],
            ]
        ]);
    }

    protected function parseSearchQuery(string $query): array
    {
        $q = trim($query);
        $q = preg_replace('/\s+/', ' ', $q) ?? $q;

        $isHashtag = preg_match('/^#([\p{L}\p{N}_]{2,64})$/u', $q, $tagMatches) === 1;
        $isMention = preg_match('/^@([a-zA-Z0-9_.]{2,64})$/', $q, $mentionMatches) === 1;

        $tag = $isHashtag ? mb_strtolower((string) ($tagMatches[1] ?? '')) : '';
        $mention = $isMention ? strtolower((string) ($mentionMatches[1] ?? '')) : '';

        $term = $q;
        if ($isHashtag) {
            $term = (string) ($tagMatches[1] ?? '');
        } elseif ($isMention) {
            $term = (string) ($mentionMatches[1] ?? '');
        }

        return [
            'raw' => $q,
            'term' => trim($term),
            'is_hashtag' => $isHashtag,
            'is_mention' => $isMention,
            'tag' => $tag,
            'mention' => $mention,
        ];
    }

    protected function buildHashtagRegex(string $tag): string
    {
        $safe = preg_quote(mb_strtolower(trim($tag)), '/');
        return '(^|[^a-z0-9_])#' . $safe . '([[:space:][:punct:]]|$)';
    }

    protected function buildMentionRegex(string $mention): string
    {
        $safe = preg_quote(strtolower(trim($mention)), '/');
        return '(^|[^a-z0-9_.])@' . $safe . '([[:space:][:punct:]]|$)';
    }
}
