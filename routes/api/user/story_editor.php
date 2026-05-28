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

Route::post('/create', [App\Http\Controllers\Api\User\Story\StoryController::class, 'create']);

Route::post('/media/upload', [App\Http\Controllers\Api\User\Story\StoryMediaController::class, 'uploadMedia']);

Route::delete('/media/delete', [App\Http\Controllers\Api\User\Story\StoryMediaController::class, 'deleteMedia']);