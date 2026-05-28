<?php
/*
|--------------------------------------------------------------------------
| Zestex - The Ultimate Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav. Full-Stack Web Developer, UI/UX Designer.
| Website: 
| E-mail: vicktbedardi9@gmail.com
| Instagram: 
| Telegram: 
|--------------------------------------------------------------------------
| Copyright (c)  Zestex. All rights reserved.
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;

// Debug endpoints
Route::get('/debug', function() {
    $postsCount = \App\Models\Post::active()->count();
    $postsToday = \App\Models\Post::active()->where('created_at', '>=', now()->startOfDay())->count();
    
    return response()->json([
        'total_active_posts' => $postsCount,
        'posts_today' => $postsToday,
        'is_working' => true,
    ]);
});

Route::get('/debug/trending', function() {
    $service = new \App\Services\Trending\TrendingService();
    
    return response()->json([
        'trending_topics' => $service->getTrendingTopics(5),
        'trending_today' => $service->getTrendingPostsToday(5),
    ]);
});

Route::post('/people', [App\Http\Controllers\Api\User\Explore\ExploreController::class, 'getPeople']);
Route::post('/posts', [App\Http\Controllers\Api\User\Explore\ExploreController::class, 'getPosts']);
Route::post('/news', [App\Http\Controllers\Api\User\Explore\ExploreController::class, 'getNews']);
Route::post('/news/show', [App\Http\Controllers\Api\User\Explore\ExploreController::class, 'getNewsItem']);

// Trending endpoints
Route::get('/trending/topics', [App\Http\Controllers\Api\User\Explore\ExploreController::class, 'getTrendingTopics']);
Route::get('/trending/today', [App\Http\Controllers\Api\User\Explore\ExploreController::class, 'getTrendingToday']);
Route::get('/trending/all', [App\Http\Controllers\Api\User\Explore\ExploreController::class, 'getAllTrending']);

// Search endpoints
Route::get('/search/hashtag', [App\Http\Controllers\Api\User\Explore\ExploreController::class, 'searchHashtag']);
Route::get('/search/keyword', [App\Http\Controllers\Api\User\Explore\ExploreController::class, 'searchKeyword']);

// Authenticated search routes
Route::prefix('search')->group(function () {
    Route::get('/posts', [App\Http\Controllers\Api\Public\Search\SearchController::class, 'searchPosts']);
    Route::get('/people', [App\Http\Controllers\Api\Public\Search\SearchController::class, 'searchPeople']);
    Route::get('/hashtags', [App\Http\Controllers\Api\Public\Search\SearchController::class, 'searchHashtags']);
});
