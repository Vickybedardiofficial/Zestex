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

Route::get('/feed', [App\Http\Controllers\Api\User\Timeline\FeedController::class, 'getFeed']);
Route::get('/update', [App\Http\Controllers\Api\User\Timeline\FeedController::class, 'getFeedUpdate']);

Route::get('/post/{hashId}', [App\Http\Controllers\Api\User\Timeline\FeedController::class, 'getPostData']);

Route::get('/post/{hashId}/comments', [App\Http\Controllers\Api\User\Timeline\FeedController::class, 'getPostComments']);

Route::post('/post/poll/vote', [App\Http\Controllers\Api\User\Timeline\PostPollController::class, 'votePoll']);

Route::post('/post/bookmarks/add', [App\Http\Controllers\Api\User\Timeline\PostController::class, 'bookmarkPost']);

Route::post('/post/reaction/add', [App\Http\Controllers\Api\User\Timeline\PostController::class, 'addReaction']);

Route::post('/post/repost/toggle', [App\Http\Controllers\Api\User\Timeline\PostController::class, 'toggleRepost']);

Route::post('/post/comment/create', [App\Http\Controllers\Api\User\Timeline\CommentController::class, 'createComment']);

Route::post('/live/start', [App\Http\Controllers\Api\User\Timeline\LiveStreamController::class, 'start']);

Route::delete('/post/delete', [App\Http\Controllers\Api\User\Timeline\PostController::class, 'deletePost']);

Route::delete('/post/comment/delete', [App\Http\Controllers\Api\User\Timeline\CommentController::class, 'deleteComment']);

Route::post('/comment/reaction/add', [App\Http\Controllers\Api\User\Timeline\CommentController::class, 'addReaction']);
