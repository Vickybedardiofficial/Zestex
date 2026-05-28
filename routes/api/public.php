<?php
/*
|--------------------------------------------------------------------------
| Public API Routes (Guest Mode)
|--------------------------------------------------------------------------
| Read-only endpoints for non-authenticated users.
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;

Route::get('/bootstrap', [App\Http\Controllers\Api\Public\Bootstrap\PublicBootstrapController::class, 'bootstrap']);

Route::prefix('timeline')->group(function () {
    Route::get('/feed', [App\Http\Controllers\Api\Public\Timeline\PublicFeedController::class, 'getFeed']);
    Route::get('/publication/{hashId}', [App\Http\Controllers\Api\Public\Timeline\PublicFeedController::class, 'getPostData']);
    Route::get('/publication/{hashId}/comments', [App\Http\Controllers\Api\Public\Timeline\PublicFeedController::class, 'getPostComments']);
});

Route::prefix('profile')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\Public\Profile\PublicProfileController::class, 'getProfileData']);
    Route::get('/posts', [App\Http\Controllers\Api\Public\Profile\PublicProfileController::class, 'getProfilePosts']);
    Route::get('/details', [App\Http\Controllers\Api\Public\Profile\PublicProfileController::class, 'getProfileDetails']);
    Route::get('/followers', [App\Http\Controllers\Api\Public\Profile\PublicProfileController::class, 'getProfileFollowers']);
    Route::get('/followings', [App\Http\Controllers\Api\Public\Profile\PublicProfileController::class, 'getProfileFollowings']);
});

Route::prefix('explore')->group(function () {
    Route::get('/posts', [App\Http\Controllers\Api\Public\Explore\PublicExploreController::class, 'getPosts']);
    Route::get('/people', [App\Http\Controllers\Api\Public\Explore\PublicExploreController::class, 'getPeople']);
    Route::get('/news', [App\Http\Controllers\Api\Public\Explore\PublicExploreController::class, 'getNews']);
    Route::get('/news/item', [App\Http\Controllers\Api\Public\Explore\PublicExploreController::class, 'getNewsItem']);
    
    // Trending endpoints (public)
    Route::get('/trending/topics', [App\Http\Controllers\Api\Public\Explore\PublicExploreController::class, 'getTrendingTopics']);
    Route::get('/trending/today', [App\Http\Controllers\Api\Public\Explore\PublicExploreController::class, 'getTrendingToday']);
    Route::get('/trending/all', [App\Http\Controllers\Api\Public\Explore\PublicExploreController::class, 'getAllTrending']);
});

Route::prefix('search')->group(function () {
    Route::get('/posts', [App\Http\Controllers\Api\Public\Search\SearchController::class, 'searchPosts']);
    Route::get('/people', [App\Http\Controllers\Api\Public\Search\SearchController::class, 'searchPeople']);
    Route::get('/hashtags', [App\Http\Controllers\Api\Public\Search\SearchController::class, 'searchHashtags']);
});
