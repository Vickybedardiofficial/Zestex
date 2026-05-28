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

Route::get('/feed', [App\Http\Controllers\Api\User\Story\StoryController::class, 'getFeed']);
Route::get('/stories/{storyId}', [App\Http\Controllers\Api\User\Story\StoryController::class, 'getStories']);
Route::get('/views/{frameId}', [App\Http\Controllers\Api\User\Story\StoryController::class, 'getStoryViews']);
Route::post('/views/record', [App\Http\Controllers\Api\User\Story\StoryController::class, 'recordView']);
Route::delete('/delete', [App\Http\Controllers\Api\User\Story\StoryController::class, 'deleteStory']);