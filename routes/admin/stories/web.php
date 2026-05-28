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

Route::get('/', [App\Http\Controllers\Admin\Story\StoryController::class, 'index'])->name('admin.stories.index');
Route::middleware('sided.layout')->get('/show/{frameId}', [App\Http\Controllers\Admin\Story\StoryController::class, 'show'])->name('admin.stories.show');
Route::post('/delete/{frameId}', [App\Http\Controllers\Admin\Story\StoryController::class, 'destroy'])->name('admin.stories.destroy');